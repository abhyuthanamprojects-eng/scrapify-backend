<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Global Attributes
        $attributesConfig = [
            'Material Type' => ['Metal', 'Plastic', 'Paper', 'Glass', 'Mixed'],
            'Pickup Size' => ['Small', 'Medium', 'Large', 'Extra Large'],
            'Condition' => ['Working', 'Scrap', 'Damaged', 'Refurbished']
        ];

        $attributeModels = [];
        foreach ($attributesConfig as $attrName => $options) {
            $attribute = \App\Models\Attribute::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($attrName)],
                [
                    'name' => ['en' => $attrName, 'hi' => $attrName], // Using English for Hindi too for now
                    'type' => 'select',
                    'status' => true
                ]
            );
            $attributeModels[$attrName] = $attribute;

            foreach ($options as $index => $optValue) {
                \App\Models\AttributeOption::updateOrCreate(
                    ['attribute_id' => $attribute->id, 'value->en' => $optValue],
                    [
                        'value' => ['en' => $optValue, 'hi' => $optValue],
                        'sort_order' => $index
                    ]
                );
            }
        }

        $data = [
            'E-Waste' => [
                'hi' => 'ई-कचरा',
                'categories' => [
                    'Computers & Laptops' => [
                        'hi' => 'कंप्यूटर और लैपटॉप',
                        'items' => [
                            'Desktop Computer' => 'डेस्कटॉप कंप्यूटर',
                            'Laptop' => 'लैपटॉप',
                            'CPU' => 'सीपीयू',
                            'Motherboard' => 'मदरबोर्ड',
                            'RAM' => 'रैम',
                            'Hard Disk Drive' => 'हार्ड डिस्क ड्राइव',
                            'Server' => 'सर्वर',
                            'Server Rack' => 'सर्वर रैक',
                        ]
                    ],
                    'Monitors & Displays' => [
                        'hi' => 'मॉनिटर और डिस्प्ले',
                        'items' => [
                            'CRT Monitor' => 'सीआरटी मॉनिटर',
                            'LCD Monitor' => 'एलसीडी मॉनिटर',
                            'LED Monitor' => 'एलईडी मॉनिटर',
                        ]
                    ],
                    'Computer Accessories' => [
                        'hi' => 'कंप्यूटर सहायक उपकरण',
                        'items' => [
                            'Mouse' => 'माउस',
                            'Keyboard' => 'कीबोर्ड',
                        ]
                    ],
                    'Printers & Office Equipment' => [
                        'hi' => 'प्रिंटर और कार्यालय उपकरण',
                        'items' => [
                            'Printer' => 'प्रिंटर',
                            'Scanner' => 'स्कैनर',
                        ]
                    ],
                    'Mobile Devices' => [
                        'hi' => 'मोबाइल उपकरण',
                        'items' => [
                            'Mobile Phone' => 'मोबाइल फोन',
                            'Smartphone' => 'स्मार्टफोन',
                            'Tablet' => 'टैबलेट',
                        ]
                    ],
                    'Power & Backup Devices' => [
                        'hi' => 'पावर और बैकअप उपकरण',
                        'items' => [
                            'Charger' => 'चार्जर',
                            'Adapter' => 'एडॉप्टर',
                            'Power Bank' => 'पावर बैंक',
                            'UPS' => 'यूपीएस',
                            'Inverter' => 'इन्वर्टर',
                            'Genset' => 'जेनसेट',
                        ]
                    ],
                    'Audio Devices' => [
                        'hi' => 'ऑडियो उपकरण',
                        'items' => [
                            'Earphones' => 'इयरफ़ोन',
                            'Headphones' => 'हेडफ़ोन',
                        ]
                    ],
                    'Home Appliances' => [
                        'hi' => 'घरेलू उपकरण',
                        'items' => [
                            'Television' => 'टेलीविज़न',
                            'Refrigerator' => 'रेफ्रिजरेटर',
                            'Washing Machine' => 'वाशिंग मशीन',
                            'Microwave' => 'माइक्रोवेव',
                            'Air Conditioner' => 'एयर कंडीशनर',
                        ]
                    ],
                    'Networking Devices' => [
                        'hi' => 'नेटवर्किंग उपकरण',
                        'items' => [
                            'Router' => 'राउटर',
                            'Switch' => 'स्विच',
                        ]
                    ],
                    'Security Devices' => [
                        'hi' => 'सुरक्षा उपकरण',
                        'items' => [
                            'CCTV Camera' => 'सीसीटीवी कैमरा',
                        ]
                    ],
                    'Lighting Equipment' => [
                        'hi' => 'प्रकाश उपकरण',
                        'items' => [
                            'LED Lights' => 'एलईडी लाइट्स',
                        ]
                    ],
                    'Cables & Wires' => [
                        'hi' => 'केबल और तार',
                        'items' => [
                            'Wires' => 'तार',
                            'Cable' => 'केबल',
                            'Power Cable' => 'पावर केबल',
                            'VGA Cable' => 'वीजीए केबल',
                        ]
                    ],
                    'Electronic Components' => [
                        'hi' => 'इलेक्ट्रॉनिक घटक',
                        'items' => [
                            'PCB' => 'पीसीबी',
                            'SMPS' => 'एसएमपीएस',
                            'Electronic Connectors' => 'इलेक्ट्रॉनिक कनेक्टर',
                            'Copper Coil' => 'कॉपर कॉइल',
                        ]
                    ],
                    'Industrial Equipment' => [
                        'hi' => 'औद्योगिक उपकरण',
                        'items' => [
                            'Control Panels' => 'कंट्रोल पैनल',
                            'ATM Machine Parts' => 'एटीएम मशीन के पुर्जे',
                        ]
                    ],
                ],
            ],
            // 'Hazardous Waste' => [
            //     'hi' => 'खतरनाक कचरा',
            //     'categories' => [
            //         'Battery Waste' => [
            //             'hi' => 'बैटरी कचरा',
            //             'items' => [
            //                 'Lead Acid Batteries (Inverter/Car)' => 'लेड एसिड बैटरी (इन्वर्टर/कार)',
            //                 'Lithium-ion Batteries (Mobile/Laptop)' => 'लिथियम-आयन बैटरी (मोबाइल/लैपटॉप)',
            //                 'Dry Cell Batteries' => 'ड्राय सेल बैटरी',
            //                 'Industrial Batteries' => 'औद्योगिक बैटरी',
            //             ]
            //         ],
            //         'Electronic Hazardous Components' => [
            //             'hi' => 'इलेक्ट्रॉनिक खतरनाक घटक',
            //             'items' => [
            //                 'PCB with Hazardous Material' => 'खतरनाक सामग्री वाला पीसीबी',
            //                 'Mercury-containing Devices' => 'पारा युक्त उपकरण',
            //                 'CFL Bulbs' => 'सीएफएल बल्ब',
            //                 'Fluorescent Tubes' => 'फ्लोरोसेंट ट्यूब',
            //             ]
            //         ],
            //         'Chemical Waste' => [
            //             'hi' => 'रासायनिक कचरा',
            //             'items' => [
            //                 'Cleaning Chemicals' => 'सफाई करने वाले रसायन',
            //                 'Industrial Chemicals' => 'औद्योगिक रसायन',
            //                 'Lab Chemicals' => 'लैब रसायन',
            //                 'Paint Thinners' => 'पेंट थिनर',
            //                 'Solvents' => 'सॉल्वेंट',
            //             ]
            //         ],
            //         'Paint & Coating Waste' => [
            //             'hi' => 'पेंट और कोटिंग कचरा',
            //             'items' => [
            //                 'Oil-based Paint' => 'ऑयल-बेस्ड पेंट',
            //                 'Emulsion Paint' => 'इमल्शन पेंट',
            //                 'Paint Containers (with residue)' => 'पेंट के डिब्बे (अवशेष के साथ)',
            //                 'Varnish & Polish' => 'वार्निश और पॉलिश',
            //             ]
            //         ],
            //         'Medical Waste (Household Level)' => [
            //             'hi' => 'चिकित्सा अपशिष्ट (घरेलू स्तर)',
            //             'items' => [
            //                 'Expired Medicines' => 'समय सीमा समाप्त दवाएं',
            //                 'Syringes' => 'सिरिंज',
            //                 'Needles' => 'सुइयां',
            //                 'Bandages (contaminated)' => 'पट्टियाँ (दूषित)',
            //             ]
            //         ],
            //         'Oil & Lubricant Waste' => [
            //             'hi' => 'तेल और लुब्रिकेंट कचरा',
            //             'items' => [
            //                 'Used Engine Oil' => 'इस्तेमाल किया हुआ इंजन तेल',
            //                 'Industrial Oil' => 'औद्योगिक तेल',
            //                 'Grease' => 'ग्रीस',
            //                 'Lubricants' => 'लुब्रिकेंट्स',
            //             ]
            //         ],
            //         'Pesticides & Agro Chemicals' => [
            //             'hi' => 'कीटनाशक और कृषि रसायन',
            //             'items' => [
            //                 'Pesticide Containers' => 'कीटनाशक कंटेनर',
            //                 'Herbicides' => 'हर्बिसाइड्स',
            //                 'Fertilizer Chemicals (Hazardous)' => 'उर्वरक रसायन (खतरनाक)',
            //             ]
            //         ],
            //         'Gas & Pressurized Containers' => [
            //             'hi' => 'गैस और दबाव वाले कंटेनर',
            //             'items' => [
            //                 'Aerosol Cans' => 'एयरोसोल कैन',
            //                 'LPG Cylinders (scrap)' => 'एलपीजी सिलेंडर (स्क्रैप)',
            //                 'Fire Extinguishers' => 'अग्निशामक यंत्र',
            //             ]
            //         ],
            //         'E-Waste Hazardous Parts' => [
            //             'hi' => 'ई-कचरा खतरनाक पुर्जे',
            //             'items' => [
            //                 'Capacitors' => 'कैपेसिटर',
            //                 'Transformers' => 'ट्रांसफार्मर',
            //                 'CRT Glass (Lead-containing)' => 'सीआरटी ग्लास (लेड युक्त)',
            //             ]
            //         ],
            //         'Contaminated Packaging' => [
            //             'hi' => 'दूषित पैकेजिंग',
            //             'items' => [
            //                 'Chemical Containers' => 'रसायन कंटेनर',
            //                 'Oil-contaminated Plastic' => 'तेल से दूषित प्लास्टिक',
            //                 'Hazardous Material Drums' => 'खतरनाक सामग्री वाले ड्रम',
            //             ]
            //         ],
            //         'Radioactive Waste (Restricted Handling)' => [
            //             'hi' => 'रेडियोधर्मी अपशिष्ट (प्रतिबंधित हैंडलिंग)',
            //             'items' => [
            //                 'Smoke Detectors' => 'धुआं डिटेक्टर',
            //                 'Specialized Industrial Sources' => 'विशेष औद्योगिक स्रोत',
            //             ]
            //         ],
            //         'Asbestos Waste' => [
            //             'hi' => 'एस्बेस्टस कचरा',
            //             'items' => [
            //                 'Asbestos Sheets' => 'एस्बेस्टस चादरें',
            //                 'Insulation Material' => 'इन्सुलेशन सामग्री',
            //             ]
            //         ],
            //     ],
            // ],
            'Metal Scrap' => [
                'hi' => 'धातु स्क्रैप',
                'categories' => [
                    'Ferrous Metals (Iron-based)' => [
                        'hi' => 'लौह धातु (लोहे पर आधारित)',
                        'items' => [
                            'Heavy Iron Scrap' => 'भारी लोहे का कबाड़',
                            'Light Iron Scrap' => 'हल्का लोहे का कबाड़',
                            'Cast Iron' => 'कास्ट आयरन',
                            'MS (Mild Steel) Scrap' => 'एमएस (माइल्ड स्टील) स्क्रैप',
                            'Iron Sheets' => 'लोहे की चादरें',
                            'Iron Rods / Bars' => 'लोहे की छड़ें',
                            'Steel Scrap' => 'स्टील स्क्रैप',
                            'Stainless Steel' => 'स्टेनलेस स्टील',
                        ]
                    ],
                    'Non-Ferrous Metals (High Value)' => [
                        'hi' => 'अलौह धातु (उच्च मूल्य)',
                        'items' => [
                            'Copper' => 'तांबा',
                            'Copper Wire' => 'तांबे का तार',
                            'Copper Coil' => 'तांबे की कुंडली',
                            'Brass' => 'पीतल',
                            'Brass Parts' => 'पीतल के पुर्जे',
                            'Aluminum' => 'एल्युमीनियम',
                            'Aluminum Sheets' => 'एल्युमीनियम की चादरें',
                            'Aluminum Utensils' => 'एल्युमीनियम के बर्तन',
                            'Lead' => 'सीसा',
                            'Zinc' => 'जस्ता',
                        ]
                    ],
                    'Mixed Metal Scrap' => [
                        'hi' => 'मिश्रित धातु स्क्रैप',
                        'items' => [
                            'Mixed Metal Items' => 'मिश्रित धातु की वस्तुएं',
                            'Industrial Mixed Scrap' => 'औद्योगिक मिश्रित स्क्रैप',
                            'Machinery Scrap (Metal Parts)' => 'मशीनरी स्क्रैप (धातु के पुर्जे)',
                        ]
                    ],
                    'Household Metal Scrap' => [
                        'hi' => 'घरेलू धातु स्क्रैप',
                        'items' => [
                            'Steel Utensils' => 'स्टील के बर्तन',
                            'Iron Furniture (Metal-based)' => 'लोहे का फर्नीचर (धातु आधारित)',
                            'Metal Buckets' => 'धातु की बाल्टियाँ',
                            'Kitchen Metal Waste' => 'रसोई धातु अपशिष्ट',
                        ]
                    ],
                    'Industrial Metal Scrap' => [
                        'hi' => 'औद्योगिक धातु स्क्रैप',
                        'items' => [
                            'Factory Scrap Metal' => 'फैक्ट्री स्क्रैप मेटल',
                            'Machine Parts' => 'मशीनरी के पुर्जे',
                            'Fabrication Waste' => 'फैब्रिकेशन अपशिष्ट',
                            'Metal Turnings / Cut Pieces' => 'धातु की टर्निंग / कटे हुए टुकड़े',
                        ]
                    ],
                    'Electrical Metal Scrap' => [
                        'hi' => 'इलेक्ट्रिकल मेटल स्क्रैप',
                        'items' => [
                            'Copper Wire (Insulated)' => 'तांबे का तार (इन्सुलेटेड)',
                            'Copper Cable' => 'तांबे का केबल',
                            'Aluminum Wire' => 'एल्युमीनियम तार',
                            'Motor Parts (Copper + Iron mix)' => 'मोटर के पुर्जे (तांबा + लोहा मिश्रण)',
                            'Transformer Metal Parts' => 'ट्रांसफार्मर धातु पुर्जे',
                        ]
                    ],
                    'Automotive Metal Scrap' => [
                        'hi' => 'ऑटोमोटिव मेटल स्क्रैप',
                        'items' => [
                            'Car Body Scrap' => 'कार बॉडी स्क्रैप',
                            'Bike Scrap Parts' => 'बाइक स्क्रैप पुर्जे',
                            'Engine Parts' => 'इंजन के पुर्जे',
                            'Alloy Wheels' => 'अलॉय व्हील',
                        ]
                    ],
                    'Construction Metal Scrap' => [
                        'hi' => 'निर्माण धातु स्क्रैप',
                        'items' => [
                            'TMT Bars' => 'टीएमटी बार',
                            'Iron Rods' => 'लोहे की छड़ें',
                            'Steel Beams' => 'स्टील बीम',
                            'Metal Pipes' => 'धातु के पाइप',
                            'Metal Frames' => 'धातु के फ्रेम',
                        ]
                    ],
                    'Precious Metal Scrap (Advanced / Optional)' => [
                        'hi' => 'कीमती धातु स्क्रैप (उन्नत / वैकल्पिक)',
                        'items' => [
                            'Silver Scrap' => 'चांदी का कबाड़',
                            'Gold Scrap (Electronics / Industrial)' => 'सोने का कबाड़ (इलेक्ट्रॉनिक्स / औद्योगिक)',
                            'Platinum (Industrial Use)' => 'प्लैटिनम (औद्योगिक उपयोग)',
                        ]
                    ],
                ],
            ],
            'Plastic Scrap' => [
                'hi' => 'प्लास्टिक स्क्रैप',
                'categories' => [
                    'Rigid Plastics' => [
                        'hi' => 'कठोर प्लास्टिक',
                        'items' => [
                            'Plastic Chairs' => 'प्लास्टिक की कुर्सियाँ',
                            'Plastic Tables' => 'प्लास्टिक की मेजें',
                            'Plastic Buckets' => 'प्लास्टिक की बाल्टियाँ',
                            'Plastic Crates' => 'प्लास्टिक के क्रेट',
                            'Plastic Drums' => 'प्लास्टिक के ड्रम',
                            'Household Hard Plastic Items' => 'घरेलू कठोर प्लास्टिक की वस्तुएं',
                        ]
                    ],
                    'Soft Plastics' => [
                        'hi' => 'नरम प्लास्टिक',
                        'items' => [
                            'Plastic Bags' => 'प्लास्टिक की थैलियां',
                            'Carry Bags' => 'कैरी बैग',
                            'Plastic Wrappers' => 'प्लास्टिक रैपर',
                            'Bubble Wrap' => 'बबल रैप',
                            'Stretch Film' => 'स्ट्रेच फिल्म',
                        ]
                    ],
                    'PET Bottles' => [
                        'hi' => 'पेट बोतलें',
                        'items' => [
                            'Water Bottles (PET)' => 'पानी की बोतलें (पीईटी)',
                            'Soft Drink Bottles' => 'सॉफ्ट ड्रिंक की बोतलें',
                            'Oil Bottles' => 'तेल की बोतलें',
                            'Transparent PET Containers' => 'पारदर्शी पीईटी कंटेनर',
                        ]
                    ],
                    'HDPE Plastics' => [
                        'hi' => 'एचडीपीई प्लास्टिक',
                        'items' => [
                            'Milk Cans' => 'दूध के डिब्बे',
                            'Shampoo Bottles' => 'शैम्पू की बोतलें',
                            'Detergent Bottles' => 'डिटर्जेंट की बोतलें',
                            'Chemical Containers (HDPE)' => 'रसायन कंटेनर (एचडीपीई)',
                        ]
                    ],
                    'PVC Plastics' => [
                        'hi' => 'पीवीसी प्लास्टिक',
                        'items' => [
                            'PVC Pipes' => 'पीवीसी पाइप',
                            'PVC Sheets' => 'पीवीसी चादरें',
                            'PVC Fittings' => 'पीवीसी फिटिंग',
                            'Electrical PVC Conduits' => 'इलेक्ट्रिकल पीवीसी नाली',
                        ]
                    ],
                    'LDPE Plastics' => [
                        'hi' => 'एलडीपीई प्लास्टिक',
                        'items' => [
                            'Plastic Covers' => 'प्लास्टिक कवर',
                            'Packaging Films' => 'पैकेजिंग फिल्में',
                            'Garbage Bags' => 'कचरा बैग',
                            'Shrink Wrap' => 'श्रिंक रैप',
                        ]
                    ],
                    'PP Plastics (Polypropylene)' => [
                        'hi' => 'पीपी प्लास्टिक (पॉलीप्रोपाइलीन)',
                        'items' => [
                            'Food Containers' => 'खाद्य कंटेनर',
                            'Lunch Boxes' => 'लंच बॉक्स',
                            'Bottle Caps' => 'बोतल के ढक्कन',
                            'Plastic Furniture Parts' => 'प्लास्टिक फर्नीचर पुर्जे',
                        ]
                    ],
                    'Industrial Plastic Scrap' => [
                        'hi' => 'औद्योगिक प्लास्टिक स्क्रैप',
                        'items' => [
                            'Plastic Moulding Waste' => 'प्लास्टिक मोल्डिंग अपशिष्ट',
                            'Injection Mould Scrap' => 'इंजेक्शन मोल्ड स्क्रैप',
                            'Plastic Factory Waste' => 'प्लास्टिक फैक्ट्री अपशिष्ट',
                        ]
                    ],
                    'E-Waste Plastics' => [
                        'hi' => 'ई-कचरा प्लास्टिक',
                        'items' => [
                            'Computer Plastic Body' => 'कंप्यूटर प्लास्टिक बॉडी',
                            'TV Plastic Parts' => 'टीवी प्लास्टिक पुर्जे',
                            'Mobile Covers (plastic)' => 'मोबाइल कवर (प्लास्टिक)',
                        ]
                    ],
                    'Mixed Plastic Scrap' => [
                        'hi' => 'मिश्रित प्लास्टिक स्क्रैप',
                        'items' => [
                            'Mixed Plastic Items' => 'मिश्रित प्लास्टिक की वस्तुएं',
                            'Multi-layer Plastics' => 'मल्टी-लेयर प्लास्टिक',
                            'Non-segregated Plastic Waste' => 'गैर-पृथक प्लास्टिक कचरा',
                        ]
                    ],
                    'Foam & Thermocol' => [
                        'hi' => 'फोम और थर्माकोल',
                        'items' => [
                            'Thermocol Sheets' => 'थर्माकोल की चादरें',
                            'Packaging Foam' => 'पैकेजिंग फोम',
                            'Foam Blocks' => 'फोम ब्लॉक',
                        ]
                    ],
                ],
            ],
            'Paper & Carton Scrap' => [
                'hi' => 'कागज और कार्टन स्क्रैप',
                'categories' => [
                    'Newspapers' => [
                        'hi' => 'समाचार पत्र',
                        'items' => [
                            'Old Newspapers' => 'पुराने समाचार पत्र',
                            'Newspaper Bundles' => 'खबरों के बंडल',
                        ]
                    ],
                    'Office Paper' => [
                        'hi' => 'कार्यालय कागज',
                        'items' => [
                            'White Office Paper' => 'सफेद कार्यालय कागज',
                            'Printed Paper' => 'मुद्रित कागज',
                            'A4 Sheets' => 'A4 शीट',
                            'Xerox Paper' => 'ज़ेरॉक्स पेपर',
                            'Files & Documents' => 'फाइलें और दस्तावेज',
                        ]
                    ],
                    'Books & Magazines' => [
                        'hi' => 'किताबें और पत्रिकाएँ',
                        'items' => [
                            'School Books' => 'स्कूल की किताबें',
                            'Notebooks' => 'नोटबुक',
                            'Magazines' => 'पत्रिकाएं',
                            'Catalogs' => 'कैटलॉग',
                        ]
                    ],
                    'Corrugated Carton (High Value)' => [
                        'hi' => 'नालीदार कार्टन (उच्च मूल्य)',
                        'items' => [
                            'Brown Carton Boxes' => 'भूरे रंग के कार्टन बॉक्स',
                            'Corrugated Sheets' => 'नालीदार चादरें',
                            'Packaging Boxes' => 'पैकेजिंग बॉक्स',
                            'Delivery Boxes' => 'डिलीवरी बॉक्स',
                        ]
                    ],
                    'Mixed Paper Scrap' => [
                        'hi' => 'मिश्रित कागज स्क्रैप',
                        'items' => [
                            'Mixed Paper Waste' => 'मिश्रित कागज अपशिष्ट',
                            'Colored Paper' => 'रंगीन कागज',
                            'Paper Scraps (Mixed Quality)' => 'कागज के टुकड़े (मिश्रित गुणवत्ता)',
                        ]
                    ],
                    'Cardboard' => [
                        'hi' => 'गत्ता',
                        'items' => [
                            'Thick Cardboard' => 'मोटा गत्ता',
                            'Carton Boards' => 'कार्टन बोर्ड',
                            'Paperboard Packaging' => 'पेपरबोर्ड पैकेजिंग',
                        ]
                    ],
                    'Shredded Paper' => [
                        'hi' => 'कटा हुआ कागज',
                        'items' => [
                            'Office Shredded Paper' => 'कार्यालय में कटा हुआ कागज',
                            'Confidential Paper Waste' => 'गोपनीय कागज अपशिष्ट',
                        ]
                    ],
                    'Kraft Paper' => [
                        'hi' => 'क्राफ्ट पेपर',
                        'items' => [
                            'Kraft Paper' => 'क्राफ्ट पेपर',
                            'Brown Kraft Paper' => 'भूरा क्राफ्ट पेपर',
                            'Wrapping Paper' => 'लपेटने वाला कागज',
                            'Paper Rolls' => 'पेपर रोल',
                        ]
                    ],
                    'Paper Cups & Plates (Low Recyclable)' => [
                        'hi' => 'कागज के कप और प्लेट (कम पुनर्चक्रण योग्य)',
                        'items' => [
                            'Used Paper Cups' => 'इस्तेमाल किए गए कागज़ के प्याले',
                            'Paper Plates' => 'कागज़ की प्लेटें',
                            'Laminated Paper Items' => 'लैमिनेटेड कागज की वस्तुएं',
                        ]
                    ],
                    'Tetra Pak (Special Category)' => [
                        'hi' => 'टेट्रा पैक (विशेष श्रेणी)',
                        'items' => [
                            'Milk Packets (Tetra Pak)' => 'दूध के पैकेट (टेट्रा पैक)',
                            'Juice Cartons' => 'जूस कार्टन',
                            'Multi-layer Paper Packaging' => 'मल्टी-लेयर पेपर पैकेजिंग',
                        ]
                    ],
                    'Industrial Paper Scrap' => [
                        'hi' => 'औद्योगिक कागज स्क्रैप',
                        'items' => [
                            'Paper Mill Waste' => 'पेपर मिल अपशिष्ट',
                            'Bulk Paper Rolls' => 'थोक पेपर रोल',
                            'Printing Industry Waste' => 'मुद्रण उद्योग अपशिष्ट',
                        ]
                    ],
                ],
            ],
            // 'Vehicle & Machinery Waste' => [
            //     'hi' => 'वाहन और मशीनरी कचरा',
            //     'categories' => [
            //         'Two-Wheeler Scrap' => [
            //             'hi' => 'दुपहिया वाहन स्क्रैप',
            //             'items' => [
            //                 'Old Bike Scrap' => 'पुरानी बाइक का कबाड़',
            //                 'Scooter Scrap' => 'स्कूटर स्क्रैप',
            //                 'Bike Engine Parts' => 'बाइक इंजन के पुर्जे',
            //                 'Two-Wheeler Metal Parts' => 'दुपहिया वाहन धातु के पुर्जे',
            //             ]
            //         ],
            //         'Four-Wheeler Scrap' => [
            //             'hi' => 'चौपहिया वाहन स्क्रैप',
            //             'items' => [
            //                 'Car Scrap (Complete Vehicle)' => 'कार स्क्रैप (पूरा वाहन)',
            //                 'Car Body Parts' => 'कार बॉडी पार्ट्स',
            //                 'Car Engine Parts' => 'कार इंजन के पुर्जे',
            //                 'Car Doors / Bonnet / Panels' => 'कार के दरवाजे / बोनट / पैनल',
            //             ]
            //         ],
            //         'Commercial Vehicle Scrap' => [
            //             'hi' => 'वाणिज्यिक वाहन स्क्रैप',
            //             'items' => [
            //                 'Truck Scrap' => 'ट्रक स्क्रैप',
            //                 'Bus Scrap' => 'बस स्क्रैप',
            //                 'Tempo / Auto Scrap' => 'टेम्पो / ऑटो स्क्रैप',
            //                 'Heavy Vehicle Parts' => 'भारी वाहन के पुर्जे',
            //             ]
            //         ],
            //         'Engine & Mechanical Parts' => [
            //             'hi' => 'इंजन और यांत्रिक पुर्जे',
            //             'items' => [
            //                 'Engines (Petrol/Diesel)' => 'इंजन (पेट्रोल/डीजल)',
            //                 'Gearbox' => 'गियरबॉक्स',
            //                 'Clutch Plates' => 'क्लच प्लेट',
            //                 'Radiators' => 'रेडिएटर',
            //                 'Silencers' => 'साइलेंसर',
            //             ]
            //         ],
            //         'Tyres & Rubber Parts' => [
            //             'hi' => 'टायर और रबर के पुर्जे',
            //             'items' => [
            //                 'Used Tyres' => 'इस्तेमाल किए गए टायर',
            //                 'Tubes' => 'ट्यूब',
            //                 'Rubber Vehicle Parts' => 'रबर वाहन पुर्जे',
            //             ]
            //         ],
            //         'Battery Scrap (Vehicle)' => [
            //             'hi' => 'बैटरी स्क्रैप (वाहन)',
            //             'items' => [
            //                 'Car Batteries' => 'कार की बैटरी',
            //                 'Bike Batteries' => 'बाइक की बैटरी',
            //                 'Heavy Vehicle Batteries' => 'भारी वाहन बैटरी',
            //             ]
            //         ],
            //         'Vehicle Electrical Parts' => [
            //             'hi' => 'वाहन इलेक्ट्रिकल पुर्जे',
            //             'items' => [
            //                 'Wiring Harness' => 'वायरिंग हार्नेस',
            //                 'Vehicle Lights' => 'वाहन की लाइटें',
            //                 'Alternators' => 'अल्टरनेटर',
            //                 'Starter Motors' => 'स्टार्टर मोटर',
            //             ]
            //         ],
            //         'Machinery Scrap (Industrial)' => [
            //             'hi' => 'मशीनरी स्क्रैप (औद्योगिक)',
            //             'items' => [
            //                 'Old Machines' => 'पुरानी मशीनें',
            //                 'Factory Equipment' => 'कारखाने के उपकरण',
            //                 'Manufacturing Machines' => 'विनिर्माण मशीनें',
            //                 'Heavy Machinery Parts' => 'भारी मशीनरी के पुर्जे',
            //             ]
            //         ],
            //         'Agricultural Machinery Scrap' => [
            //             'hi' => 'कृषि मशीनरी स्क्रैप',
            //             'items' => [
            //                 'Tractor Scrap' => 'ट्रैक्टर स्क्रैप',
            //                 'Farming Equipment' => 'खेती के उपकरण',
            //                 'Harvest Machine Parts' => 'हार्वेस्ट मशीन के पुर्जे',
            //             ]
            //         ],
            //         'Construction Machinery Scrap' => [
            //             'hi' => 'निर्माण मशीनरी स्क्रैप',
            //             'items' => [
            //                 'JCB Parts' => 'जीसीबी के पुर्जे',
            //                 'Excavator Parts' => 'उत्खनन के पुर्जे',
            //                 'Crane Parts' => 'क्रेन के पुर्जे',
            //                 'Drilling Machines' => 'ड्रिलिंग मशीनें',
            //             ]
            //         ],
            //         'Metal Body Scrap' => [
            //             'hi' => 'मेटल बॉडी स्क्रैप',
            //             'items' => [
            //                 'Vehicle Chassis' => 'वाहन हवाई जहाज़ के पहिये',
            //                 'Metal Frames' => 'धातु के फ्रेम',
            //                 'Structural Vehicle Parts' => 'संरचनात्मक वाहन पुर्जे',
            //             ]
            //         ],
            //         'Mixed Vehicle Scrap' => [
            //             'hi' => 'मिश्रित वाहन स्क्रैप',
            //             'items' => [
            //                 'Mixed Vehicle Parts' => 'मिश्रित वाहन पुर्जे',
            //                 'Unsorted Machinery Scrap' => 'अनसॉर्टेड मशीनरी स्क्रैप',
            //             ]
            //         ],
            //     ],
            // ],
            'Furniture Scrap' => [
                'hi' => 'फर्नीचर स्क्रैप',
                'categories' => [
                    'Wooden Furniture' => [
                        'hi' => 'लकड़ी का फर्नीचर',
                        'items' => [
                            'Wooden Bed' => 'लकड़ी का पलंग',
                            'Wooden Table' => 'लकड़ी की मेज',
                            'Wooden Chair' => 'लकड़ी की कुर्सी',
                            'Wooden Sofa' => 'लकड़ी का सोफा',
                            'Wooden Wardrobe' => 'लकड़ी की अलमारी',
                            'Wooden Cabinets' => 'लकड़ी की कैबिनेट',
                            'Plywood Furniture' => 'प्लाईवुड फर्नीचर',
                            'Wooden Doors / Windows' => 'लकड़ी के दरवाजे / खिड़कियां',
                        ]
                    ],
                    'Metal Furniture' => [
                        'hi' => 'धातु फर्नीचर',
                        'items' => [
                            'Iron Bed' => 'लोहे का पलंग',
                            'Steel Chairs' => 'स्टील की कुर्सियां',
                            'Steel Tables' => 'स्टील की मेजें',
                            'Metal Cabinets' => 'धातु की कैबिनेट',
                            'Office Metal Furniture' => 'कार्यालय धातु फर्नीचर',
                            'Storage Racks' => 'स्टोरेज रैक',
                        ]
                    ],
                    'Plastic Furniture' => [
                        'hi' => 'प्लास्टिक फर्नीचर',
                        'items' => [
                            'Plastic Chairs' => 'प्लास्टिक की कुर्सियाँ',
                            'Plastic Tables' => 'प्लास्टिक की मेजें',
                            'Plastic Stools' => 'प्लास्टिक के स्टूल',
                            'Outdoor Plastic Furniture' => 'बाहरी प्लास्टिक फर्नीचर',
                        ]
                    ],
                    'Office Furniture' => [
                        'hi' => 'कार्यालय फर्नीचर',
                        'items' => [
                            'Office Chairs (Revolving)' => 'कार्यालय कुर्सियाँ (घूमने वाली)',
                            'Office Tables' => 'कार्यालय की मेजें',
                            'Workstations' => 'वर्कस्टेशन',
                            'Partitions' => 'विभाजन',
                            'Conference Tables' => 'सम्मेलन की मेजें',
                            'Filing Cabinets' => 'फाइलिंग कैबिनेट',
                        ]
                    ],
                    'Upholstered Furniture' => [
                        'hi' => 'असबाबवाला फर्नीचर',
                        'items' => [
                            'Sofa Sets' => 'सोफा सेट',
                            'Cushioned Chairs' => 'गद्दीदार कुर्सियाँ',
                            'Recliners' => 'रिक्लाइनर',
                            'Mattresses' => 'गद्दे',
                        ]
                    ],
                    'Glass Furniture' => [
                        'hi' => 'कांच का फर्नीचर',
                        'items' => [
                            'Glass Tables' => 'कांच की मेजें',
                            'Glass Shelves' => 'कांच की अलमारियां',
                            'Glass Cabinets' => 'कांच की अलमारियां',
                        ]
                    ],
                    'Modular Furniture' => [
                        'hi' => 'मॉड्यूलर फर्नीचर',
                        'items' => [
                            'Modular Kitchen Units' => 'मॉड्यूलर किचन इकाइयाँ',
                            'Modular Wardrobes' => 'मॉड्यूलर वार्डरोब',
                            'TV Units' => 'टीवी इकाइयां',
                            'Wall Panels' => 'दीवार पैनल',
                        ]
                    ],
                    'Institutional Furniture' => [
                        'hi' => 'संस्थागत फर्नीचर',
                        'items' => [
                            'School Benches' => 'स्कूल की बेंचें',
                            'Desks' => 'डेस्क',
                            'Hospital Beds' => 'अस्पताल के बिस्तर',
                            'Waiting Area Chairs' => 'प्रतिक्षा क्षेत्र की कुर्सियाँ',
                        ]
                    ],
                    'Outdoor Furniture' => [
                        'hi' => 'आउटडोर फर्नीचर',
                        'items' => [
                            'Garden Chairs' => 'गार्डन कुर्सियाँ',
                            'Patio Furniture' => 'आंगन फर्नीचर',
                            'Balcony Furniture' => 'बालकनी फर्नीचर',
                        ]
                    ],
                    'Mixed Furniture Scrap' => [
                        'hi' => 'मिश्रित फर्नीचर स्क्रैप',
                        'items' => [
                            'Broken Furniture Pieces' => 'फर्नीचर के टूटे हुए टुकड़े',
                            'Mixed Material Furniture' => 'मिश्रित सामग्री फर्नीचर',
                            'Unsorted Furniture Waste' => 'अनसॉर्टेड फर्नीचर कचरा',
                        ]
                    ],
                ],
            ],
        ];

        foreach ($data as $typeName => $typeData) {
            $categoryType = \App\Models\CategoryType::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($typeName)],
                [
                    'name' => [
                        'en' => $typeName,
                        'hi' => $typeData['hi']
                    ],
                    'image_path' => 'category_types/' . \Illuminate\Support\Str::slug($typeName) . '.png',
                    'status' => true,
                    'show_in_corporate_booking' => false,
                ]
            );

            // Delete old top-level category if it exists (since it's now a CategoryType)
            \App\Models\Category::where('slug', \Illuminate\Support\Str::slug($typeName))->delete();

            foreach ($typeData['categories'] as $categoryName => $catData) {
                $categorySlug = \Illuminate\Support\Str::slug($typeName . '-' . $categoryName);
                $category = Category::updateOrCreate(
                    ['slug' => $categorySlug],
                    [
                        'name' => [
                            'en' => $categoryName,
                            'hi' => $catData['hi']
                        ],
                        'parent_id' => null,
                        'category_type_id' => $categoryType->id,
                        'image_path' => 'category/' . $categorySlug . '.png',
                        'status' => true,
                    ]
                );

                foreach ($catData['items'] as $itemName => $hiItemName) {
                    $itemSlug = \Illuminate\Support\Str::slug($categoryName . '-' . $itemName);
                    $item = Category::updateOrCreate(
                        ['slug' => $itemSlug],
                        [
                            'name' => [
                                'en' => $itemName,
                                'hi' => $hiItemName
                            ],
                            'parent_id' => $category->id,
                            'category_type_id' => $categoryType->id,
                            'image_path' => 'category/' . $category->id . '/' . $itemSlug . '.png',
                            'status' => true,
                        ]
                    );

                    // Add Default Pricing Rule
                    $pricingType = 'per_kg'; // Default for most items including RAM, Cables, Scraps, etc.

                    // Standard Pricing Map based on user's market image
                    $marketPrices = [
                        'Car Scrap (Complete Vehicle)' => 18.00,
                        'Computer Plastic Body' => 15.00,
                        'Desktop Computer' => 40.00,
                        'Laptop' => 44.00,
                        'CPU' => 25.00,
                        'Motherboard' => 69.00,
                        'RAM' => 53.00,
                        'Hard Disk Drive' => 33.00,
                        'Server' => 84.00,
                        'Air Conditioner' => 4500.00,
                        'Television' => 1200.00,
                        'Refrigerator' => 2500.00,
                        'Washing Machine' => 1800.00,
                        'Microwave' => 800.00,
                    ];

                    // Determine pricing type based on item name or category
                    $lowercaseItemName = strtolower($itemName);
                    $lowercaseCategoryName = strtolower($categoryName);
                    $lowercaseTypeName = strtolower($typeName);

                    // Determine base price
                    $basePrice = $marketPrices[$itemName] ?? rand(10, 100);

                    // Only large complete appliances or units are per_piece
                    if (
                        str_contains($lowercaseItemName, 'air conditioner') ||
                        str_contains($lowercaseItemName, 'ac') ||
                        str_contains($lowercaseItemName, 'television') ||
                        str_contains($lowercaseItemName, 'refrigerator') ||
                        str_contains($lowercaseItemName, 'washing machine') ||
                        str_contains($lowercaseItemName, 'microwave') ||
                        str_contains($lowercaseItemName, 'genset') ||
                        str_contains($lowercaseTypeName, 'furniture') ||
                        (str_contains($lowercaseCategoryName, 'vehicle') && !str_contains($lowercaseItemName, 'part') && !str_contains($lowercaseItemName, 'scrap'))
                    ) {
                        $pricingType = 'per_piece';
                    }

                    // Special case for items shown in image as per_kg even if they are "complete"
                    if (isset($marketPrices[$itemName])) {
                        // Most items in the user image are per_kg
                        if ($itemName !== 'Air Conditioner' && $itemName !== 'Television' && $itemName !== 'Refrigerator' && $itemName !== 'Washing Machine' && $itemName !== 'Microwave') {
                            $pricingType = 'per_kg';
                        }
                    }

                    // Explicitly override to per_kg for parts and scrap-heavy categories
                    if (
                        str_contains($lowercaseItemName, 'part') ||
                        str_contains($lowercaseItemName, 'scrap') ||
                        str_contains($lowercaseItemName, 'ram') ||
                        str_contains($lowercaseItemName, 'internal') ||
                        str_contains($lowercaseItemName, 'cable') ||
                        str_contains($lowercaseItemName, 'wire') ||
                        str_contains($lowercaseTypeName, 'paper') ||
                        str_contains($lowercaseTypeName, 'metal') ||
                        str_contains($lowercaseTypeName, 'plastic')
                    ) {
                        $pricingType = 'per_kg';
                    }

                    \App\Models\PricingRule::updateOrCreate(
                        ['category_id' => $item->id],
                        [
                            'pricing_type' => $pricingType,
                            'base_price' => $basePrice,
                            'min_quantity' => 1
                        ]
                    );

                    // Sync random attributes for metadata
                    $syncData = [];
                    foreach ($attributeModels as $attrName => $attr) {
                        $syncData[$attr->id] = ['is_required' => true];
                    }
                    $item->attributes()->sync($syncData);
                }
            }
        }
    }
}
