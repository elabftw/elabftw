<?php
require_once('inc/auth.php');
require_once('inc/connect.php');

// LOOP NB
if( (isset($_POST['loop'])) && (!empty($_POST['loop'])) && (filter_var($_POST['loop'], FILTER_VALIDATE_INT))){
    $loopnb = $_POST['loop'];
} else {
    $loopnb = 200;
}

// TITLES 
$title_arr = array();
$title_arr[] = 'HeLa on Spinning 4 with Y27632 and RAC-1 photoactivable';
$title_arr[] = 'HeLa-H2b-mCherry on Zeiss Orientation on patterns';
$title_arr[] = 'RAC-1 photoactivable try 2';
$title_arr[] = 'Patterns sur du PDMS avec du napalm';
$title_arr[] = 'Patterns avec Mauricette';
$title_arr[] = 'HeLa-LifeAct-mCherry on Biostation for migration with electric field';

// BODYS
$body_arr = array();
$body_arr[] = "In this report, we describe a reliable protocol for biocytin labeling of neuronal tissue and diaminobenzidine (DAB)-based processing of brain slices. We describe how to embed tissues in different media and how to subsequently histochemically label the tissues for light or electron microscopic examination. 
    
    We provide a detailed dehydration and embedding protocol using Eukitt that avoids the common problem of tissue distortion and therefore prevents fading of cytoarchitectural features (in particular, lamination) of brain tissue; as a result, additional labeling methods (such as cytochrome oxidase staining) become unnecessary. 
    
    In addition, we provide correction factors for tissue shrinkage in all spatial dimensions so that a realistic neuronal morphology can be obtained from slice preparations. Such corrections were hitherto difficult to calculate because embedding in viscous media resulted in highly nonlinear tissue deformation. 
    Fixation, immunocytochemistry and embedding procedures for light microscopy (LM) can be completed within 42–48 h. Subsequent reconstructions and morphological analyses take an additional 24 h or more.";

$body_arr[] = "We describe a method for the efficient and selective identification of DNA containing the 5-hydroxymethylcytosine (5-hmC) modification. 
    
    
    This protocol takes advantage of two proteins: T4 β-glucosyltransferase (β-gt), which converts 5-hmC to β-glucosyl-5-hmC (β-glu-5-hmC), and J-binding protein 1 (JBP1), which specifically recognizes and binds to β-glu-5-hmC. We describe the steps necessary to purify JBP1 and modify this protein such that it can be fixed to magnetic beads. 
    
    Thereafter, we detail how to use the JBP1 magnetic beads to obtain DNA that is enriched with 5-hmC. This method is likely to produce results similar to those of other 5-hmC pull-down assays; however, all necessary components for the completion of this protocol are readily available or can be easily and rapidly synthesized using basic molecular biology techniques. 
    
    This protocol can be completed in less than 2 weeks and allows the user to isolate 5-hmC-containing genomic DNA that is suitable for analysis by quantitative PCR (qPCR), sequencing, microarray and other molecular biology assays.";

$body_arr[] = "We describe here a protocol for culturing epicardial cells from adult zebrafish hearts, which have a unique regenerative capacity after injury. 
    
    Briefly, zebrafish hearts first undergo ventricular amputation or sham operation. Next, the hearts are excised and explanted onto fibrin gels prepared in advance in a multiwell tissue culture plate. The procedure allows the epicardial cells to outgrow from the ventricle onto a fibrin matrix in vitro. 
    
    
    
    This protocol differs from those used in other organisms by using a fibrin gel to mimic blood clots that normally form after injury and that are essential for proper cell migration. 
    The culture procedure can be accomplished within 5 h; epicardial cells can be obtained within 24–48 h and can be maintained in culture for 5–6 d. 
    This protocol can be used to investigate the mechanisms underlying epicardial cell migration, proliferation and epithelial-to-mesenchymal transition during heart regeneration, homeostatic cardiac growth or other physiological processes.";

$body_arr[] = "This protocol uses rat tail–derived type I collagen hydrogels to analyze key processes in developmental neurobiology, such as chemorepulsion and chemoattraction. The method is based on culturing small pieces of brain tissue from embryonic or early perinatal mice inside a 3D hydrogel formed by rat tail–derived type I collagen or, alternatively, by commercial Matrigel. 
    The neural tissue is placed in the hydrogel with other brain tissue pieces or cell aggregates genetically modified to secrete a particular molecule that can generate a gradient inside the hydrogel. 
    
    The present method is uncomplicated and generally reproducible, and only a few specific details need to be considered during its preparation. 
    
    
    Moreover, the degree and behavior of axonal growth or neural migration can be observed directly using phase-contrast, fluorescence microscopy or immunocytochemical methods. This protocol can be carried out in 4 weeks.";

$body_arr[] = "This protocol describes the isolation and characterization of mouse and human esophageal epithelial cells and the application of 3D organotypic culture (OTC), a form of tissue engineering. 
    This model system permits the interrogation of mechanisms underlying epithelial-stromal interactions. We provide guidelines for isolating and cultivating several sources of epithelial cells and fibroblasts, as well as genetic manipulation of these cell types, as a prelude to their integration into OTC. 
    
    
    The protocol includes a number of important applications, including histology, immunohistochemistry/immunofluorescence, genetic modification of epithelial cells and fibroblasts with retroviral and lentiviral vectors for overexpression of genes or RNA interference strategies, confocal imaging, laser capture microdissection, RNA microarrays of individual cellular compartments and protein-based assays. The OTC (3D) culture protocol takes 15 d to perform.";

$body_arr[] = "To understand the role of physical forces at a cellular level, it is necessary to track mechanical properties during cellular processes. Here we present a protocol that uses flat atomic force microscopy (AFM) cantilevers clamped at constant height, and light microscopy to measure the resistance force, mechanical stress and volume of globular animal cells under compression. We describe the AFM and cantilever setup, live cell culture in the AFM, how to ensure stability of AFM measurements during medium perfusion, integration of optical microscopy to measure parameters such as volume and track intracellular dynamics, and interpretation of the physical parameters measured. 
    
    
    Although we use this protocol on trypsinized interphase and mitotic HeLa cells, it can also be applied to other cells with a relatively globular shape, especially animal cells in a low-adhesive environment. 
    After a short setup phase, the protocol can be used to investigate approximately one cell per hour.";

$body_arr[] = "High-throughput ballistic injection nanorheology is a method for the quantitative study of cell mechanics. 
    
    
    Cell mechanics are measured by ballistic injection of submicron particles into the cytoplasm of living cells and tracking the spontaneous displacement of the particles at high spatial resolution. 
    The trajectories of the cytoplasm-embedded particles are transformed into mean-squared displacements, which are subsequently transformed into frequency-dependent viscoelastic moduli and time-dependent creep compliance of the cytoplasm. 
    This method allows for the study of a wide range of cellular conditions, including cells inside a 3D matrix, cell subjected to shear flows and biochemical stimuli, and cells in a live animal. 
    Ballistic injection lasts <1 min and is followed by overnight incubation. Multiple particle tracking for one cell lasts <1 min. Forty cells can be examined in <1 h.";

$body_arr[] = "We describe a strategy for analyzing axonal transport of cytosolic proteins (CPs) using photoactivatable GFP—PAGFP—with modifications of standard imaging components that can be retroactively fitted to a conventional epifluorescence microscope. 
    
    The photoactivation and visualization are nearly simultaneous, allowing studies of proteins with rapidly mobile fractions. 
    Cultured hippocampal neurons are transfected with PAGFP-tagged constructs, a discrete protein population within axons is photoactivated, and then the activated population is tracked by live imaging. 
    
    We show the utility of this method in analyzing axonal transport of CPs that have inherent diffusible pools and distinguish this transport modality from passive diffusion and vesicle transport. 
    The analytical tools used to quantify the motion are also described. Aside from the time needed for preparation of neuronal cultures/transfection, the experiment takes 2–3 h, during which time several axons can be imaged and analyzed. These methods should be easy to adopt by most laboratories and may also be useful for monitoring CP movement in other cell types.";


// TAGS
$tags_arr = array();
$tags_arr[] = 'hela';
$tags_arr[] = 'fibronectin';
$tags_arr[] = 'spin4';
$tags_arr[] = 'spin5';
$tags_arr[] = 'video3';
$tags_arr[] = 'video classique';
$tags_arr[] = 'PDMS';
$tags_arr[] = 'channels';
$tags_arr[] = 'stretching';
$tags_arr[] = 'chmp4b';
$tags_arr[] = 'orientation';
$tags_arr[] = 'migration';
$tags_arr[] = 'spinbdd';
$tags_arr[] = 'frap';
$tags_arr[] = 'photoactivable';
$tags_arr[] = 'collaboration';
$tags_arr[] = 'FRET';
$tags_arr[] = 'amplification';
$tags_arr[] = 'plasmid';
$tags_arr[] = 'RPE-1';
$tags_arr[] = 'dc';
$tags_arr[] = 'cytocinese';
$tags_arr[] = 'test';

// DATES
$date_arr = array();
$date_arr[] = '100212';
$date_arr[] = '120201';
$date_arr[] = '111114';
$date_arr[] = '120116';
$date_arr[] = '100918';
$date_arr[] = '100819';
$date_arr[] = '100723';
$date_arr[] = '100625';
$date_arr[] = '100530';
$date_arr[] = '100430';
$date_arr[] = '100312';
$date_arr[] = '110912';
$date_arr[] = '110123';
$date_arr[] = '110207';

// OUTCOMES
$outcome_arr = array();
$outcome_arr[] = 'success';
$outcome_arr[] = 'fail';
$outcome_arr[] = 'redo';
$outcome_arr[] = 'running';

///////////////////////////////////////
// LOOP
for($i=0; $i<$loopnb; $i++){
    shuffle($title_arr);
    shuffle($date_arr);
    shuffle($body_arr);
    shuffle($outcome_arr);
    $title = $title_arr[0];
    $body = $body_arr[0];
    $date = $date_arr[0];
    $outcome = $outcome_arr[0];

    // SQL
    $sql = "INSERT INTO experiments(title, date, body, outcome, userid) VALUES(:title, :date, :body, :outcome, :userid)";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'title' => $title,
        'date' => $date,
        'body' => $body,
        'outcome' => $outcome,
        'userid' => $_SESSION['userid']));
    // Get what is the experiment id we just created
    $sql = "SELECT id FROM experiments WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
    $req = $bdd->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid']);
    $req->execute();
    $data = $req->fetch();
    $newid = $data['id'];
    $tag = array_rand($tags_arr, 1);
    // SQL for addtag
    for($j=0;$j<6;$j++){
        shuffle($tags_arr);
        $tag = $tags_arr[0];
        $sql = "INSERT INTO experiments_tags (tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'tag' => $tag,
            'userid' => $_SESSION['userid'],
            'item_id' => $newid));
    }
}
// END LOOP
////////////////////////////////////////

$msg_arr = array();
$msg_arr[] = 'Successfully created '.$loopnb.' fake experiments :)';
$_SESSION['infos'] = $msg_arr;
header('location: experiments.php?mode=show');
?>
