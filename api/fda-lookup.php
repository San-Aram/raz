<?php
session_start();

// Debug logging
error_log("FDA API called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'not set'));

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

header('Content-Type: application/json');

try {
    // Handle both GET and POST requests
    $activeIngredient = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['medication'])) {
        $activeIngredient = trim($_GET['medication']);
        error_log("GET request - medication: " . $activeIngredient);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $activeIngredient = trim($input['active_ingredient'] ?? '');
        error_log("POST request - active_ingredient: " . $activeIngredient);
    }
    
    if (empty($activeIngredient)) {
        error_log("Empty active ingredient");
        echo json_encode([
            'success' => false,
            'message' => 'Active ingredient is required'
        ]);
        exit;
    }
    
    // Try different search strategies
    $fdaData = null;
    $drugBankData = null;
    $searchTerms = generateSearchTerms($activeIngredient);
    
    // Try FDA search with different terms
    foreach ($searchTerms as $term) {
        error_log("Trying FDA search with term: " . $term);
        $fdaData = searchFDADatabase($term);
        if ($fdaData) {
            error_log("FDA data found with term: " . $term);
            break;
        }
    }
    
    // Try DrugBank search
    foreach ($searchTerms as $term) {
        error_log("Trying DrugBank search with term: " . $term);
        $drugBankData = searchDrugBankDatabase($term);
        if ($drugBankData) {
            error_log("DrugBank data found with term: " . $term);
            break;
        }
    }
    
    if ($fdaData || $drugBankData) {
        echo json_encode([
            'success' => true,
            'data' => [
                'fda' => $fdaData,
                'drugbank' => $drugBankData
            ],
            'original_ingredient' => $activeIngredient,
            'search_terms_used' => $searchTerms
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No FDA or DrugBank data found for this active ingredient',
            'searched_terms' => $searchTerms,
            'suggestions' => [
                'Try using the generic name instead of brand name',
                'Check the spelling of the medication name',
                'Use the active ingredient name only (without dosage)'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("FDA API Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching FDA data: ' . $e->getMessage()
    ]);
}

/**
 * Generate multiple search terms for better matching
 */
function generateSearchTerms($ingredient) {
    $terms = [];
    $original = trim($ingredient);
    
    // Add original term
    $terms[] = $original;
    
    // Add lowercase version
    $terms[] = strtolower($original);
    
    // Add title case version
    $terms[] = ucfirst(strtolower($original));
    
    // Common drug name mappings
    $mappings = [
        'paracetamol' => ['acetaminophen', 'APAP', 'N-acetyl-p-aminophenol'],
        'acetaminophen' => ['paracetamol', 'APAP'],
        'aspirin' => ['acetylsalicylic acid', 'ASA'],
        'ibuprofen' => ['isobutylphenylpropionic acid'],
        'omeprazole' => ['5-methoxy-2-[[(4-methoxy-3,5-dimethyl-2-pyridinyl)methyl]sulfinyl]-1H-benzimidazole'],
        'metformin' => ['1,1-dimethylbiguanide', 'dimethylbiguanide'],
        'amoxicillin' => ['amoxycillin'],
        'lisinopril' => ['lysine analog of enalaprilat']
    ];
    
    $lowerOriginal = strtolower($original);
    if (isset($mappings[$lowerOriginal])) {
        foreach ($mappings[$lowerOriginal] as $alt) {
            $terms[] = $alt;
            $terms[] = ucfirst($alt);
        }
    }
    
    // Check reverse mappings
    foreach ($mappings as $key => $alternatives) {
        if (in_array($lowerOriginal, array_map('strtolower', $alternatives))) {
            $terms[] = $key;
            $terms[] = ucfirst($key);
        }
    }
    
    // Remove duplicates and return
    return array_unique($terms);
}

/**
 * Search FDA database using openFDA API
 */
function searchFDADatabase($ingredient) {
    try {
        $searchTerm = urlencode($ingredient);
        
        // Try different FDA search strategies
        $searchUrls = [
            "https://api.fda.gov/drug/label.json?search=active_ingredient:\"$searchTerm\"&limit=1",
            "https://api.fda.gov/drug/label.json?search=active_ingredient:$searchTerm&limit=1",
            "https://api.fda.gov/drug/label.json?search=openfda.generic_name:\"$searchTerm\"&limit=1",
            "https://api.fda.gov/drug/label.json?search=openfda.brand_name:\"$searchTerm\"&limit=1",
            "https://api.fda.gov/drug/label.json?search=openfda.substance_name:\"$searchTerm\"&limit=1"
        ];
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'PharmaCare/1.0',
                'method' => 'GET'
            ]
        ]);
        
        foreach ($searchUrls as $url) {
            error_log("Trying FDA URL: " . $url);
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if (isset($data['results']) && !empty($data['results'])) {
                    $result = $data['results'][0];
                    
                    return [
                        'source' => 'FDA',
                        'brand_name' => $result['openfda']['brand_name'][0] ?? 'Not available',
                        'generic_name' => $result['openfda']['generic_name'][0] ?? $ingredient,
                        'manufacturer_name' => $result['openfda']['manufacturer_name'][0] ?? 'Not available',
                        'product_type' => $result['openfda']['product_type'][0] ?? 'Not available',
                        'route' => isset($result['openfda']['route']) ? implode(', ', $result['openfda']['route']) : 'Not available',
                        'substance_name' => isset($result['openfda']['substance_name']) ? implode(', ', $result['openfda']['substance_name']) : $ingredient,
                        'purpose' => isset($result['purpose']) ? (is_array($result['purpose']) ? implode(' ', $result['purpose']) : $result['purpose']) : 'Not available',
                        'indications_and_usage' => isset($result['indications_and_usage']) ? (is_array($result['indications_and_usage']) ? implode(' ', $result['indications_and_usage']) : $result['indications_and_usage']) : 'Not available',
                        'dosage_and_administration' => isset($result['dosage_and_administration']) ? (is_array($result['dosage_and_administration']) ? implode(' ', $result['dosage_and_administration']) : $result['dosage_and_administration']) : 'Not available',
                        'warnings' => isset($result['warnings']) ? (is_array($result['warnings']) ? implode(' ', $result['warnings']) : $result['warnings']) : 'Not available',
                        'adverse_reactions' => isset($result['adverse_reactions']) ? (is_array($result['adverse_reactions']) ? implode(' ', $result['adverse_reactions']) : $result['adverse_reactions']) : 'Not available',
                        'drug_interactions' => isset($result['drug_interactions']) ? (is_array($result['drug_interactions']) ? implode(' ', $result['drug_interactions']) : $result['drug_interactions']) : 'Not available',
                        'ndc' => isset($result['openfda']['product_ndc']) ? implode(', ', array_slice($result['openfda']['product_ndc'], 0, 3)) : 'Not available',
                        'application_number' => isset($result['openfda']['application_number']) ? implode(', ', $result['openfda']['application_number']) : 'Not available'
                    ];
                }
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("FDA search exception: " . $e->getMessage());
        return null;
    }
}

/**
 * Search DrugBank database using local data
 */
function searchDrugBankDatabase($ingredient) {
    try {
        // Enhanced local database with more medications
        $drugsDatabase = [
            'paracetamol' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB00316',
                'name' => 'Acetaminophen',
                'description' => 'Acetaminophen is an analgesic and antipyretic agent used for the relief of fever and mild to moderate pain.',
                'cas_number' => '103-90-2',
                'indication' => 'For the relief of mild to moderate pain and fever',
                'mechanism_of_action' => 'Inhibits cyclooxygenase enzymes and blocks prostaglandin synthesis',
                'half_life' => '1-4 hours',
                'absorption' => 'Well absorbed from gastrointestinal tract (85-95%)',
                'toxicity' => 'Overdose can cause severe liver damage',
                'metabolism' => 'Primarily metabolized in the liver'
            ],
            'acetaminophen' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB00316',
                'name' => 'Acetaminophen',
                'description' => 'Acetaminophen is an analgesic and antipyretic agent used for the relief of fever and mild to moderate pain.',
                'cas_number' => '103-90-2',
                'indication' => 'For the relief of mild to moderate pain and fever',
                'mechanism_of_action' => 'Inhibits cyclooxygenase enzymes and blocks prostaglandin synthesis',
                'half_life' => '1-4 hours',
                'absorption' => 'Well absorbed from gastrointestinal tract (85-95%)',
                'toxicity' => 'Overdose can cause severe liver damage',
                'metabolism' => 'Primarily metabolized in the liver'
            ],
            'amoxicillin' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB01060',
                'name' => 'Amoxicillin',
                'description' => 'A broad-spectrum penicillin antibiotic used to treat bacterial infections.',
                'cas_number' => '26787-78-0',
                'indication' => 'Treatment of bacterial infections including respiratory, urinary tract, and skin infections',
                'mechanism_of_action' => 'Inhibits bacterial cell wall synthesis by binding to penicillin-binding proteins',
                'half_life' => '1-1.5 hours',
                'absorption' => 'Well absorbed orally (75-90%)',
                'toxicity' => 'Generally well tolerated; allergic reactions possible',
                'metabolism' => 'Minimal hepatic metabolism'
            ],
            'metformin' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB00331',
                'name' => 'Metformin',
                'description' => 'A biguanide antidiabetic drug used as first-line treatment for type 2 diabetes.',
                'cas_number' => '657-24-9',
                'indication' => 'Treatment of type 2 diabetes mellitus as monotherapy or in combination',
                'mechanism_of_action' => 'Decreases hepatic glucose production and improves insulin sensitivity',
                'half_life' => '4-9 hours',
                'absorption' => 'Approximately 50-60% absorbed from small intestine',
                'toxicity' => 'Risk of lactic acidosis, especially in kidney impairment',
                'metabolism' => 'Not metabolized; excreted unchanged in urine'
            ],
            'lisinopril' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB00722',
                'name' => 'Lisinopril',
                'description' => 'An ACE inhibitor used to treat hypertension and heart failure.',
                'cas_number' => '83915-83-7',
                'indication' => 'Treatment of hypertension, heart failure, and post-myocardial infarction',
                'mechanism_of_action' => 'Inhibits angiotensin-converting enzyme, reducing angiotensin II formation',
                'half_life' => '12 hours',
                'absorption' => 'Approximately 25% absorbed, not affected by food',
                'toxicity' => 'May cause angioedema, hyperkalemia, and kidney dysfunction',
                'metabolism' => 'Not metabolized; excreted unchanged in urine'
            ],
            'omeprazole' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB00338',
                'name' => 'Omeprazole',
                'description' => 'A proton pump inhibitor used to treat gastroesophageal reflux disease.',
                'cas_number' => '73590-58-6',
                'indication' => 'Treatment of GERD, peptic ulcers, and Zollinger-Ellison syndrome',
                'mechanism_of_action' => 'Inhibits H+/K+-ATPase enzyme in gastric parietal cells',
                'half_life' => '0.5-1 hour',
                'absorption' => 'Rapidly absorbed, bioavailability increases with repeated dosing',
                'toxicity' => 'Generally well tolerated; long-term use may affect B12 and magnesium levels',
                'metabolism' => 'Extensively metabolized by CYP2C19 and CYP3A4'
            ],
            'ibuprofen' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB01050',
                'name' => 'Ibuprofen',
                'description' => 'A nonsteroidal anti-inflammatory drug (NSAID) used for pain and inflammation.',
                'cas_number' => '15687-27-1',
                'indication' => 'Treatment of pain, fever, and inflammation',
                'mechanism_of_action' => 'Non-selective inhibition of COX-1 and COX-2 enzymes',
                'half_life' => '2-4 hours',
                'absorption' => 'Rapidly and completely absorbed from GI tract',
                'toxicity' => 'GI bleeding, kidney damage, and cardiovascular risks with long-term use',
                'metabolism' => 'Extensively metabolized in the liver'
            ],
            'aspirin' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB00945',
                'name' => 'Aspirin',
                'description' => 'An NSAID used for pain relief, inflammation reduction, and cardiovascular protection.',
                'cas_number' => '50-78-2',
                'indication' => 'Pain relief, anti-inflammatory, antipyretic, and cardiovascular protection',
                'mechanism_of_action' => 'Irreversibly inhibits COX-1 and COX-2 through acetylation',
                'half_life' => '2-3 hours for analgesic effect',
                'absorption' => 'Rapidly absorbed from stomach and small intestine',
                'toxicity' => 'GI bleeding, Reye syndrome in children, salicylate poisoning',
                'metabolism' => 'Hydrolyzed to salicylic acid'
            ],
            'atorvastatin' => [
                'source' => 'DrugBank',
                'drugbank_id' => 'DB01076',
                'name' => 'Atorvastatin',
                'description' => 'An HMG-CoA reductase inhibitor used to lower cholesterol levels.',
                'cas_number' => '134523-00-5',
                'indication' => 'Treatment of hypercholesterolemia and prevention of cardiovascular disease',
                'mechanism_of_action' => 'Inhibits HMG-CoA reductase, the rate-limiting enzyme in cholesterol synthesis',
                'half_life' => '14 hours',
                'absorption' => 'Rapidly absorbed, bioavailability about 14%',
                'toxicity' => 'Muscle pain, liver enzyme elevation, rare rhabdomyolysis',
                'metabolism' => 'Extensively metabolized by CYP3A4'
            ]
        ];
        
        $searchKey = strtolower(trim($ingredient));
        
        // Direct match
        if (isset($drugsDatabase[$searchKey])) {
            return $drugsDatabase[$searchKey];
        }
        
        // Partial match
        foreach ($drugsDatabase as $key => $drugData) {
            if (strpos($searchKey, $key) !== false || strpos($key, $searchKey) !== false) {
                return $drugData;
            }
            
            // Check against drug name
            if (isset($drugData['name']) && 
                (stripos($drugData['name'], $searchKey) !== false || 
                 stripos($searchKey, $drugData['name']) !== false)) {
                return $drugData;
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("DrugBank search exception: " . $e->getMessage());
        return null;
    }
}
?>
