<x-guest-layout fullscreen>
    <style>
        @keyframes capsule-float {

            0%,
            100% {
                transform: translateY(0) rotate(var(--r));
            }

            50% {
                transform: translateY(-14px) rotate(calc(var(--r) + 6deg));
            }
        }

        .capsule {
            animation: capsule-float 7s ease-in-out infinite;
        }

        .capsule:nth-child(2) {
            animation-delay: -2s;
            animation-duration: 9s;
        }

        .capsule:nth-child(3) {
            animation-delay: -4s;
            animation-duration: 8s;
        }

        .capsule:nth-child(4) {
            animation-delay: -1s;
            animation-duration: 10s;
        }

        @media (prefers-reduced-motion: reduce) {
            .capsule {
                animation: none;
            }
        }
    </style>

    {{-- PHLE Sample Questions Pool --}}
    @php
        $sampleQuestions = [
            // Pharmacology
            [
                'subject' => 'Pharmacology',
                'question' => 'Which drug is the antidote for acetaminophen overdose?',
                'options' => ['a' => 'Naloxone', 'b' => 'N-acetylcysteine', 'c' => 'Flumazenil', 'd' => 'Atropine'],
                'correct' => 'b',
                'explanation' => 'N-acetylcysteine restores hepatic glutathione stores and protects the liver from NAPQI toxicity.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which antidiabetic drug works primarily by decreasing hepatic glucose production?',
                'options' => ['a' => 'Glibenclamide', 'b' => 'Metformin', 'c' => 'Pioglitazone', 'd' => 'Acarbose'],
                'correct' => 'b',
                'explanation' => 'Metformin is a biguanide that inhibits gluconeogenesis and glycogenolysis in the liver.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'What is the mechanism of action of Aspirin at low doses (75-81 mg/day)?',
                'options' => ['a' => 'Irreversible COX-1 inhibition', 'b' => 'Reversible COX-2 inhibition', 'c' => 'Thrombin receptor blockade', 'd' => 'Phosphodiesterase inhibition'],
                'correct' => 'a',
                'explanation' => 'Low-dose aspirin irreversibly acetylates COX-1, preventing Thromboxane A2 synthesis in platelets.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which drug class is considered first-line for hypertensive patients with diabetic nephropathy?',
                'options' => ['a' => 'Beta blockers', 'b' => 'ACE inhibitors', 'c' => 'Thiazide diuretics', 'd' => 'Alpha blockers'],
                'correct' => 'b',
                'explanation' => 'ACE inhibitors (e.g., Enalapril) reduce intraglomerular pressure and slow the progression of renal disease.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which antibiotic class is associated with tendon rupture as a rare adverse effect?',
                'options' => ['a' => 'Fluoroquinolones', 'b' => 'Macrolides', 'c' => 'Tetracyclines', 'd' => 'Aminoglycosides'],
                'correct' => 'a',
                'explanation' => 'Fluoroquinolones (e.g., Ciprofloxacin) carry a black box warning for tendinitis and tendon rupture.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which opioid receptor agonist is used specifically to manage opioid withdrawal and dependence?',
                'options' => ['a' => 'Fentanyl', 'b' => 'Methadone', 'c' => 'Oxycodone', 'd' => 'Morphine'],
                'correct' => 'b',
                'explanation' => 'Methadone has a long half-life, preventing withdrawal symptoms without producing acute euphoria.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which loop diuretic acts by inhibiting the Na+/K+/2Cl- cotransporter in the thick ascending limb of Henle?',
                'options' => ['a' => 'Spironolactone', 'b' => 'Furosemide', 'c' => 'Hydrochlorothiazide', 'd' => 'Acetazolamide'],
                'correct' => 'b',
                'explanation' => 'Furosemide blocks the NKCC2 symporter, leading to potent diuresis and excretion of sodium and water.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'What is the primary mechanism of action of Warfarin?',
                'options' => ['a' => 'Inhibits Factor Xa', 'b' => 'Inhibits Vitamin K epoxide reductase', 'c' => 'Activates Antithrombin III', 'd' => 'Blocks P2Y12 ADP receptors'],
                'correct' => 'b',
                'explanation' => 'Warfarin blocks VKORC1, reducing active Vitamin K needed for synthesis of factors II, VII, IX, and X.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which antiarrhythmic is classified as a Class III potassium channel blocker?',
                'options' => ['a' => 'Lidocaine', 'b' => 'Amiodarone', 'c' => 'Verapamil', 'd' => 'Propranolol'],
                'correct' => 'b',
                'explanation' => 'Amiodarone primarily blocks potassium channels to prolong action potential duration and effective refractory period.'
            ],
            [
                'subject' => 'Pharmacology',
                'question' => 'Which inhaled anesthetic has the lowest blood-gas partition coefficient, leading to rapid induction and recovery?',
                'options' => ['a' => 'Halothane', 'b' => 'Desflurane', 'c' => 'Isoflurane', 'd' => 'Sevoflurane'],
                'correct' => 'b',
                'explanation' => 'Desflurane has a very low blood-gas solubility, resulting in the fastest onset and clearance among inhaled agents.'
            ],

            // Pharmaceutics
            [
                'subject' => 'Pharmaceutics',
                'question' => 'Which equation describes the rate of drug dissolution from a solid dosage form?',
                'options' => ['a' => 'Henderson-Hasselbalch equation', 'b' => 'Noyes-Whitney equation', 'c' => 'Michaelis-Menten equation', 'd' => 'Arrhenius equation'],
                'correct' => 'b',
                'explanation' => 'The Noyes-Whitney equation correlates dissolution rate with surface area, solubility, and diffusion coefficient.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'What type of flow property is demonstrated by tragacanth and sodium alginate in suspension?',
                'options' => ['a' => 'Dilatant', 'b' => 'Plastic', 'c' => 'Pseudoplastic', 'd' => 'Newtonian'],
                'correct' => 'c',
                'explanation' => 'Pseudoplastic flow (shear-thinning) decreases viscosity as shear rate increases, ideal for suspensions.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'According to USP guidelines, what is the temperature range defined as "Cool"?',
                'options' => ['a' => '2°C to 8°C', 'b' => '8°C to 15°C', 'c' => '15°C to 30°C', 'd' => '-20°C to -10°C'],
                'correct' => 'b',
                'explanation' => 'USP defines Cool as 8°C–15°C, Cold/Refrigerated as 2°C–8°C, and Controlled Room Temp as 20°C–25°C.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'Which surfactant HLB range is suitable for preparing Oil-in-Water (O/W) emulsions?',
                'options' => ['a' => '3 to 6', 'b' => '8 to 16', 'c' => '1 to 3', 'd' => '16 to 18'],
                'correct' => 'b',
                'explanation' => 'Hydrophilic surfactants with HLB values between 8–16 favor the formation of O/W emulsions.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'Which excipient is commonly used as a disintegrant in tablet formulation?',
                'options' => ['a' => 'Magnesium stearate', 'b' => 'Sodium starch glycolate', 'c' => 'Lactose', 'd' => 'Talc'],
                'correct' => 'b',
                'explanation' => 'Sodium starch glycolate is a superdisintegrant that swells rapidly in water to break apart the tablet.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'What type of suppository base melts at body temperature rather than dissolving in mucosal fluids?',
                'options' => ['a' => 'Glycerinated gelatin', 'b' => 'Polyethylene glycol', 'c' => 'Cocoa butter (Theobroma oil)', 'd' => 'Carbowax'],
                'correct' => 'c',
                'explanation' => 'Cocoa butter is a oleaginous base that melts near body temperature (~34°C–35°C).'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'Which particle size reduction method relies on attrition and impact in a high-speed rotating chamber with air streams?',
                'options' => ['a' => 'Fluid energy mill', 'b' => 'Ball mill', 'c' => 'Colloid mill', 'd' => 'Hammer mill'],
                'correct' => 'a',
                'explanation' => 'Fluid energy milling uses high-velocity air streams causing inter-particle collisions and attrition.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'In parenteral manufacturing, which pyrogen test utilizes the lysate from horseshoe crab blood cells?',
                'options' => ['a' => 'Rabbit pyrogen test', 'b' => 'LAL assay', 'c' => 'Gram stain test', 'd' => 'Endotoxin ELISA'],
                'correct' => 'b',
                'explanation' => 'Limulus Amebocyte Lysate (LAL) test detects bacterial endotoxins in parenteral preparations.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'What phenomenon occurs when a liquid phase separates into two layers in an emulsion due to droplet coalescence?',
                'options' => ['a' => 'Creaming', 'b' => 'Cracking', 'c' => 'Flocculation', 'd' => 'Phase inversion'],
                'correct' => 'b',
                'explanation' => 'Cracking (breaking) is an irreversible separation where the protective emulsifier film is destroyed.'
            ],
            [
                'subject' => 'Pharmaceutics',
                'question' => 'Which capsule size represents the smallest capacity for human oral administration?',
                'options' => ['a' => 'Size 000', 'b' => 'Size 00', 'c' => 'Size 0', 'd' => 'Size 5'],
                'correct' => 'd',
                'explanation' => 'Capsule sizes range from 000 (largest) down to 5 (smallest standard human size).'
            ],

            // Pharmacognosy
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which phytochemical test is used to detect the presence of alkaloids forming a reddish-brown precipitate?',
                'options' => ['a' => 'Mayer’s test', 'b' => 'Wagner’s test', 'c' => 'Dragendorff’s test', 'd' => 'Keller-Kiliani test'],
                'correct' => 'b',
                'explanation' => 'Wagner’s reagent (iodine in potassium iodide) forms a reddish-brown precipitate with alkaloids.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which plant source produces the cardiac glycoside Digoxin?',
                'options' => ['a' => 'Atropa belladonna', 'b' => 'Digitalis lanata', 'c' => 'Catharanthus roseus', 'd' => 'Rauvolfia serpentina'],
                'correct' => 'b',
                'explanation' => 'Digitalis lanata (Grecian Foxglove) is the primary commercial source of Digoxin.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'What active constituent derived from Willow Bark served as the precursor to modern Aspirin?',
                'options' => ['a' => 'Salicin', 'b' => 'Quinine', 'c' => 'Reserpine', 'd' => 'Morphine'],
                'correct' => 'a',
                'explanation' => 'Salicin isolated from Salix alba was hydrolyzed to salicylic acid and acetylated to produce aspirin.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which alkaloid isolated from Catharanthus roseus is widely used as a chemotherapeutic agent?',
                'options' => ['a' => 'Vincristine', 'b' => 'Atropine', 'c' => 'Codeine', 'd' => 'Pilocarpine'],
                'correct' => 'a',
                'explanation' => 'Vincristine and Vinblastine are vinca alkaloids that inhibit microtubule polymerization in cancer cells.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which classification of volatile oils is derived from Cinnamomum verum?',
                'options' => ['a' => 'Aldehyde volatile oil', 'b' => 'Phenol volatile oil', 'c' => 'Ester volatile oil', 'd' => 'Ketone volatile oil'],
                'correct' => 'a',
                'explanation' => 'Cinnamon oil contains cinnamaldehyde, which is categorized under aldehyde volatile oils.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'What carbohydrate polymer obtained from marine brown algae is used as a gelling agent and binder?',
                'options' => ['a' => 'Agar', 'b' => 'Sodium alginate', 'c' => 'Acacia', 'd' => 'Pectin'],
                'correct' => 'b',
                'explanation' => 'Sodium alginate is extracted from brown seaweeds (Phaeophyceae) like Macrocystis pyrifera.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which reagent is used specifically to test for deoxysugars present in cardiac glycosides?',
                'options' => ['a' => 'Keller-Kiliani test', 'b' => 'Froehde’s test', 'c' => 'Bial’s test', 'd' => 'Barfoed’s test'],
                'correct' => 'a',
                'explanation' => 'Keller-Kiliani test produces a reddish-brown ring turning blue-green for digitoxose deoxysugars.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which resin extracted from Ferula foetida is known for its strong pungent odor and carminative action?',
                'options' => ['a' => 'Asafoetida', 'b' => 'Myrrh', 'c' => 'Frankincense', 'd' => 'Mastic'],
                'correct' => 'a',
                'explanation' => 'Asafoetida is an oleo-gum-resin obtained from Ferula species, traditionally used as an antispasmodic.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which central nervous system stimulant is an alkaloid present in Coffea arabica and Camellia sinensis?',
                'options' => ['a' => 'Theophylline', 'b' => 'Caffeine', 'c' => 'Theobromine', 'd' => 'Nicotine'],
                'correct' => 'b',
                'explanation' => 'Caffeine (1,3,7-trimethylxanthine) is a xanthine alkaloid present in coffee beans and tea leaves.'
            ],
            [
                'subject' => 'Pharmacognosy',
                'question' => 'Which medicinal herb, locally known as Sambong, is officially recognized in the Philippines as a diuretic and anti-urolithiasis drug?',
                'options' => ['a' => 'Blumea balsamifera', 'b' => 'Psidium guajava', 'c' => 'Mentha cordifolia', 'd' => 'Carmona retusa'],
                'correct' => 'a',
                'explanation' => 'Sambong (Blumea balsamifera) is clinically validated in the Philippines for dissolving kidney stones.'
            ],

            // Pharmaceutical Chemistry
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'Which functional group is responsible for the beta-lactamase sensitivity in penicillins?',
                'options' => ['a' => 'Thiazolidine ring', 'b' => 'Four-membered cyclic amide ring', 'c' => 'Carboxylic acid group', 'd' => 'Acyl side chain'],
                'correct' => 'b',
                'explanation' => 'The 4-membered cyclic amide (beta-lactam ring) is hydrolyzed and opened by bacterial beta-lactamase enzymes.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'What is the chemical name of Paracetamol?',
                'options' => ['a' => 'Acetylsalicylic acid', 'b' => 'N-acetyl-p-aminophenol', 'c' => '2-(4-isobutylphenyl)propanoic acid', 'd' => '4-amino-N-2-thiazolylbenzenesulfonamide'],
                'correct' => 'b',
                'explanation' => 'Paracetamol (Acetaminophen) is chemically identified as N-acetyl-p-aminophenol (APAP).'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'Which functional group increases the lipophilicity and blood-brain barrier penetration of drugs?',
                'options' => ['a' => 'Hydroxyl group', 'b' => 'Aromatic/Alkyl group', 'c' => 'Sulfonic acid group', 'd' => 'Amine group'],
                'correct' => 'b',
                'explanation' => 'Lipophilic moieties like alkyl chains and aromatic rings facilitate passive diffusion across lipid membranes.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'Which isomerism phenomenon occurs when two drugs are non-superimposable mirror images of each other?',
                'options' => ['a' => 'Geometric isomerism', 'b' => 'Enantiomerism', 'c' => 'Tautomerism', 'd' => 'Structural isomerism'],
                'correct' => 'b',
                'explanation' => 'Enantiomers are optical isomers that are non-superimposable mirror images around a chiral center.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'Which structural modification to Penicillin G confers acid stability for oral administration?',
                'options' => ['a' => 'Adding an electron-withdrawing group at the side chain', 'b' => 'Adding a bulky side chain ring', 'c' => 'Esterification of the carboxyl group', 'd' => 'Removing the thiazolidine ring'],
                'correct' => 'a',
                'explanation' => 'Electron-withdrawing groups (as in Penicillin V) reduce acid hydrolysis in stomach acid.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'What type of metabolic reaction involves Oxidation, Reduction, and Hydrolysis?',
                'options' => ['a' => 'Phase I reactions', 'b' => 'Phase II reactions', 'c' => 'Glucuronidation', 'd' => 'Conjugation reactions'],
                'correct' => 'a',
                'explanation' => 'Phase I biotransformations introduce or expose functional groups via oxidation, reduction, or hydrolysis.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'Which cytochrome P450 enzyme isoform metabolizes approximately 50% of therapeutic drugs?',
                'options' => ['a' => 'CYP2D6', 'b' => 'CYP3A4', 'c' => 'CYP2C9', 'd' => 'CYP1A2'],
                'correct' => 'b',
                'explanation' => 'CYP3A4 is the most abundant liver P450 enzyme, responsible for metabolizing over half of commercial drugs.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'What structural feature gives sulfonamide antibacterial agents competitive antagonism with PABA?',
                'options' => ['a' => 'Structural similarity to para-aminobenzoic acid', 'b' => 'Beta-lactam core', 'c' => 'Phenanthrene ring system', 'd' => 'Steroidal nucleus'],
                'correct' => 'a',
                'explanation' => 'Sulfonamides mimic PABA and inhibit dihydropteroate synthase in bacterial folic acid synthesis.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'Which structural modification converts Morphine into Codeine?',
                'options' => ['a' => 'Diacetylation of phenolic and alcoholic OH', 'b' => '3-O-methylation of the phenolic OH', 'c' => 'Demethylation of the nitrogen', 'd' => 'Reduction of the double bond'],
                'correct' => 'b',
                'explanation' => 'Codeine is 3-O-methylmorphine, which reduces analgesic potency but improves oral bioavailability.'
            ],
            [
                'subject' => 'Pharmaceutical Chemistry',
                'question' => 'What is the key functional group in the structure of Nitroglycerin responsible for vasodilation?',
                'options' => ['a' => 'Nitrate ester', 'b' => 'Thiol', 'c' => 'Nitrobenzene', 'd' => 'Amide'],
                'correct' => 'a',
                'explanation' => 'Nitroglycerin (glyceryl trinitrate) releases nitric oxide from its organic nitrate ester groups.'
            ],

            // Biochemistry
            [
                'subject' => 'Biochemistry',
                'question' => 'Which metabolic pathway produces the highest yield of ATP per molecule of glucose oxidized under aerobic conditions?',
                'options' => ['a' => 'Glycolysis', 'b' => 'Oxidative Phosphorylation', 'c' => 'Pentose Phosphate Pathway', 'd' => 'Gluconeogenesis'],
                'correct' => 'b',
                'explanation' => 'Oxidative phosphorylation in the mitochondrial inner membrane produces ~28 to 30 ATP per glucose molecule.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'Which enzyme converts glucose to glucose-6-phosphate in the liver during high plasma glucose levels?',
                'options' => ['a' => 'Hexokinase', 'b' => 'Glucokinase', 'c' => 'Phosphofructokinase-1', 'd' => 'Glucose-6-phosphatase'],
                'correct' => 'b',
                'explanation' => 'Glucokinase (Hexokinase IV) has a high Km for glucose and functions primarily in the liver and pancreas.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'Which amino acid is classified as essential because human cells cannot synthesize its carbon skeleton?',
                'options' => ['a' => 'Alanine', 'b' => 'Leucine', 'c' => 'Glycine', 'd' => 'Glutamate'],
                'correct' => 'b',
                'explanation' => 'Leucine is an essential branched-chain amino acid that must be supplied through dietary sources.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'What key coenzyme derived from Vitamin B3 acts as an electron carrier in cellular respiration?',
                'options' => ['a' => 'FAD', 'b' => 'NAD+', 'c' => 'CoA-SH', 'd' => 'PLP'],
                'correct' => 'b',
                'explanation' => 'Nicotinamide adenine dinucleotide (NAD+) is derived from Niacin and carries electrons in redox reactions.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'Which enzyme is the rate-limiting step of cholesterol biosynthesis in human liver tissue?',
                'options' => ['a' => 'HMG-CoA reductase', 'b' => 'Lecithin-cholesterol acyltransferase', 'c' => 'Squalene synthase', 'd' => 'Lipoprotein lipase'],
                'correct' => 'a',
                'explanation' => 'HMG-CoA reductase converts HMG-CoA to mevalonate and is targeted by statins.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'What nitrogenous base is present in RNA but absent in DNA?',
                'options' => ['a' => 'Thymine', 'b' => 'Uracil', 'c' => 'Cytosine', 'd' => 'Adenine'],
                'correct' => 'b',
                'explanation' => 'Uracil pyrimidine base pairs with adenine in RNA instead of thymine found in DNA.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'Which disease is caused by a genetic deficiency in the enzyme Glucose-6-Phosphate Dehydrogenase (G6PD)?',
                'options' => ['a' => 'Hemolytic anemia under oxidative stress', 'b' => 'Phenylketonuria', 'c' => 'Gouty arthritis', 'd' => 'Scurvy'],
                'correct' => 'a',
                'explanation' => 'G6PD deficiency impairs NADPH production, making RBCs vulnerable to oxidative damage and lysis.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'Which lipoprotein transport particle has the highest proportion of triacylglycerols?',
                'options' => ['a' => 'LDL', 'b' => 'Chylomicrons', 'c' => 'HDL', 'd' => 'VLDL'],
                'correct' => 'b',
                'explanation' => 'Chylomicrons transport dietary lipids and consist of over 85%–90% triglycerides by mass.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'What is the primary product of beta-oxidation of even-chain fatty acids?',
                'options' => ['a' => 'Pyruvate', 'b' => 'Acetyl-CoA', 'c' => 'Oxaloacetate', 'd' => 'Succinate'],
                'correct' => 'b',
                'explanation' => 'Each cycle of beta-oxidation cleaves two carbons from acyl-CoA to yield Acetyl-CoA.'
            ],
            [
                'subject' => 'Biochemistry',
                'question' => 'Which bond stabilizes the alpha-helix and beta-sheet secondary structures of proteins?',
                'options' => ['a' => 'Peptide bonds', 'b' => 'Hydrogen bonds', 'c' => 'Disulfide bridges', 'd' => 'Ionic bonds'],
                'correct' => 'b',
                'explanation' => 'Hydrogen bonding between carbonyl oxygens and amide hydrogens stabilizes protein secondary structures.'
            ],

            // Microbiology
            [
                'subject' => 'Microbiology',
                'question' => 'Which bacterial structure is responsible for conferring Gram-negative bacteria their endotoxic activity?',
                'options' => ['a' => 'Peptidoglycan', 'b' => 'Lipopolysaccharide (Lipid A)', 'c' => 'Teichoic acid', 'd' => 'Flagellin'],
                'correct' => 'b',
                'explanation' => 'Lipid A, the toxic component of Lipopolysaccharide (LPS), triggers intense immune and inflammatory responses.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'What type of sterilization process utilizes saturated steam under pressure in an autoclave?',
                'options' => ['a' => 'Dry heat sterilization', 'b' => 'Moist heat sterilization', 'c' => 'Gaseous sterilization', 'd' => 'Radiation sterilization'],
                'correct' => 'b',
                'explanation' => 'Autoclaving uses moist heat under pressure (121°C at 15 psi for 15-20 min) to denature bacterial proteins.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'Which Gram-positive bacterium is arranged in grape-like clusters and tests Positive for catalase and coagulase?',
                'options' => ['a' => 'Streptococcus pyogenes', 'b' => 'Staphylococcus aureus', 'c' => 'Enterococcus faecalis', 'd' => 'Streptococcus pneumoniae'],
                'correct' => 'b',
                'explanation' => 'Staphylococcus aureus is a catalase-positive and coagulase-positive coccus forming cluster formations.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'Which staining technique is used as the primary diagnostic tool for Mycobacterium tuberculosis?',
                'options' => ['a' => 'Gram stain', 'b' => 'Acid-fast stain (Ziehl-Neelsen)', 'c' => 'Schaeffer-Fulton stain', 'd' => 'Negative stain'],
                'correct' => 'b',
                'explanation' => 'Mycobacteria have high mycolic acid cell walls that resist decolorization by acid-alcohol.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'Which physical agent is used for cold sterilization of heat-sensitive surgical and pharmaceutical equipment?',
                'options' => ['a' => 'Autoclave steam', 'b' => 'Ethylene oxide gas', 'c' => 'Hot air oven', 'd' => 'Boiling water'],
                'correct' => 'b',
                'explanation' => 'Ethylene oxide alkylates cellular proteins and DNA, serving as a gas sterilant for heat-labile goods.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'What fungal pathogen is the most common cause of oral thrush and vulvovaginal candidiasis?',
                'options' => ['a' => 'Aspergillus fumigatus', 'b' => 'Candida albicans', 'c' => 'Cryptococcus neoformans', 'd' => 'Histoplasma capsulatum'],
                'correct' => 'b',
                'explanation' => 'Candida albicans is an opportunistic dimorphic fungus causing mucosal and systemic infections.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'Which bacterial gene transfer mechanism involves direct contact via a sex pilus?',
                'options' => ['a' => 'Transformation', 'b' => 'Conjugation', 'c' => 'Transduction', 'd' => 'Transposition'],
                'correct' => 'b',
                'explanation' => 'Conjugation involves plasmid transfer between donor and recipient bacteria through a conjugation bridge.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'Which antibody isotype is the primary immunoglobin present in secretions like saliva, tears, and colostrum?',
                'options' => ['a' => 'IgG', 'b' => 'IgA', 'c' => 'IgM', 'd' => 'IgE'],
                'correct' => 'b',
                'explanation' => 'Secretory IgA forms a mucosal immune barrier preventing pathogen attachment to epithelial surfaces.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'What is the mechanism of action of Amphotericin B in treating systemic fungal infections?',
                'options' => ['a' => 'Binds to ergosterol to form membrane pores', 'b' => 'Inhibits 1,3-beta-glucan synthesis', 'c' => 'Inhibits squalene epoxidase', 'd' => 'Inhibits fungal RNA synthesis'],
                'correct' => 'a',
                'explanation' => 'Amphotericin B binds to fungal ergosterol, creating pores that leak ions and cause cell death.'
            ],
            [
                'subject' => 'Microbiology',
                'question' => 'Which virus is a enveloped, single-stranded RNA virus belonging to the Retroviridae family and contains Reverse Transcriptase?',
                'options' => ['a' => 'Hepatitis B Virus', 'b' => 'Human Immunodeficiency Virus (HIV)', 'c' => 'Influenza Virus', 'd' => 'Epstein-Barr Virus'],
                'correct' => 'b',
                'explanation' => 'HIV uses reverse transcriptase to transcribe viral RNA into cDNA for insertion into human host genomes.'
            ]
        ];
    @endphp

    {{-- Full-screen breakout container compatible across all devices --}}
    <div class="min-h-screen bg-slate-50 lg:bg-white text-slate-800">
        <div class="min-h-screen grid grid-cols-1 lg:grid-cols-[0.80fr_1fr]">

            {{-- ============ LEFT: brand + live sample question (Desktop / Large Screens) ============ --}}
            <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-[#4A2FC4] p-8 xl:p-12 text-white min-h-screen">

                {{-- Floating capsules --}}
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="capsule absolute -top-4 right-16 h-10 w-28 rounded-full bg-[linear-gradient(90deg,#fbbf24_50%,#ffffff_50%)] opacity-90 shadow-lg" style="--r:-28deg"></div>
                    <div class="capsule absolute top-1/3 -right-6 h-9 w-24 rounded-full bg-[linear-gradient(90deg,#34d399_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:35deg"></div>
                    <div class="capsule absolute bottom-24 -left-6 h-10 w-28 rounded-full bg-[linear-gradient(90deg,#fb7185_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:20deg"></div>
                    <div class="capsule absolute bottom-6 right-24 h-8 w-20 rounded-full bg-[linear-gradient(90deg,#38bdf8_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:-15deg"></div>
                </div>

                {{-- Brand Header --}}
                <div class="relative flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 font-display text-xl font-extrabold shadow-lg rotate-[-6deg]">
                        Rx
                    </div>
                    <div>
                        <p class="font-display text-2xl font-extrabold leading-none">CitiRx</p>
                        <p class="mt-1 text-xs font-medium text-indigo-100">PHLE adaptive prep</p>
                    </div>
                </div>

                {{-- Headline + interactive sample widget --}}
                <div class="relative max-w-lg my-auto py-8">
                    <h2 class="font-display text-4xl xl:text-5xl font-extrabold leading-[1.08] tracking-tight">
                        Every question you answer gets you closer to your license.
                    </h2>
                    <p class="mt-4 text-sm xl:text-base text-indigo-100">
                        Adaptive practice that spends your time on the topics you miss most. Try one right now.
                    </p>

                    <div
                        x-data="sampleQuiz({{ Js::from($sampleQuestions) }})"
                        class="mt-8 rounded-3xl bg-white p-6 text-slate-800 shadow-2xl shadow-indigo-900/30">
                        
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700" x-text="current.subject"></span>
                            <span>Sample question</span>
                        </div>

                        <p class="mt-4 font-display text-base xl:text-lg font-semibold leading-snug text-slate-900" x-text="current.question">
                        </p>

                        <div class="mt-4 grid gap-2">
                            <template x-for="(label, key) in current.options" :key="key">
                                <button
                                    type="button"
                                    @click="picked = key"
                                    :disabled="picked !== null"
                                    class="flex items-center gap-3 rounded-xl border px-4 py-2.5 text-left text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                    :class="{
                                        'border-slate-200 hover:border-indigo-300 hover:bg-indigo-50': picked === null,
                                        'border-emerald-400 bg-emerald-50 text-emerald-800': picked !== null && key === current.correct,
                                        'border-rose-300 bg-rose-50 text-rose-800': picked === key && key !== current.correct,
                                        'border-slate-100 text-slate-400': picked !== null && key !== current.correct && picked !== key
                                    }">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600" x-text="key.toUpperCase()"></span>
                                    <span x-text="label"></span>
                                </button>
                            </template>
                        </div>

                        <div x-show="picked !== null" x-cloak x-transition class="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-relaxed text-slate-600">
                            <template x-if="picked === current.correct">
                                <p><span class="font-bold text-emerald-700">Correct.</span> <span x-text="current.explanation"></span></p>
                            </template>
                            <template x-if="picked !== current.correct">
                                <p><span class="font-bold text-rose-700">Not quite.</span> The answer is <span class="font-semibold" x-text="current.options[current.correct]"></span>. <span x-text="current.explanation"></span></p>
                            </template>

                            {{-- Navigation Buttons Bar --}}
                            <div class="mt-3 flex items-center justify-between border-t border-slate-200/60 pt-2.5">
                                <button
                                    type="button"
                                    @click="prevQuestion()"
                                    :disabled="history.length === 0"
                                    class="font-semibold text-slate-500 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Previous question
                                </button>

                                <button
                                    type="button"
                                    @click="nextQuestion()"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    Try another question
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="relative text-xs text-indigo-100">Built for future pharmacists.</p>
            </aside>

            {{-- ============ RIGHT: sign-in form ============ --}}
            <main class="flex flex-col items-center justify-center px-6 py-10 sm:px-10 lg:px-12 xl:px-16 bg-white min-h-screen">

                {{-- Mobile / Tablet Brand Header --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-fuchsia-600 text-white font-display text-xl font-extrabold shadow-lg rotate-[-6deg]">
                        Rx
                    </div>
                    <div>
                        <p class="font-display text-2xl font-extrabold leading-none text-slate-900">CitiRx</p>
                        <p class="mt-1 text-xs font-medium text-slate-400">PHLE adaptive prep</p>
                    </div>
                </div>

                <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-indigo-100/60 lg:border-0 lg:p-0 lg:shadow-none">
                    <div class="mb-6 sm:mb-8 text-left sm:text-left">
                        <h1 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Welcome back</h1>
                        <p class="mt-2 text-sm text-slate-500">Sign in to pick up your review where you left off.</p>
                    </div>

                    {{-- Native Validation Errors --}}
                    @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
                        <div class="font-bold">Whoops! Something went wrong.</div>
                        <ul class="mt-2 list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @session('status')
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
                        {{ $value }}
                    </div>
                    @endsession

                    <form method="POST" action="{{ route('login') }}" class="space-y-5"
                        x-data="{ show: false, busy: false }"
                        @submit="busy = true"
                        @pageshow.window="busy = false">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="student@citirx.edu">
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-fuchsia-600">Forgot password?</a>
                                @endif
                            </div>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </span>
                                <input id="password" type="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-16 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="Your password">
                                <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-indigo-600 focus:outline-none focus-visible:text-indigo-600"
                                    x-text="show ? 'Hide' : 'Show'">Show</button>
                            </div>
                        </div>

                        <label for="remember_me" class="flex cursor-pointer items-center gap-2">
                            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-600">Keep me signed in on this device</span>
                        </label>

                        <button type="submit"
                            :disabled="busy"
                            style="--lip: #4A2FC4;"
                            class="btn-press flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-7 py-3.5 font-display text-base font-bold text-white shadow-md hover:bg-indigo-700 disabled:opacity-70 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-200">
                            <span x-text="busy ? 'Signing in…' : 'Sign In'">Sign In</span>
                        </button>
                    </form>

                    @if (Route::has('register'))
                    <p class="mt-8 text-center text-sm text-slate-500">
                        New to CitiRx?
                        <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-fuchsia-600">Create a free account</a>
                    </p>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-guest-layout>