<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\type\RegionType;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use App\Utils\Platform\Wix\WixClient;
use App\Entity\CompanyApplication;
use App\Entity\SettingsValue;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Webkul\Modules\Wix\WixmpBundle\Entity\Collections;
use Webkul\Modules\Wix\WixmpBundle\Events\CatalogEvent;
use Symfony\Component\Validator\Constraints\Image;

class CatalogHelper extends WixMpBaseHelper
{
    public function getProductForm($product,$seller = null)
    {
        $formFactory = $this->container->get('form.factory');

        $discountTypes = [
            'percentage' => 'PERCENT',
            'fixed' => 'AMOUNT',
        ];
        $inventoryStatus = [
            'in_stock' => true,
            'out_of_stock' => false
        ];
        $form = $formFactory->createBuilder(FormType::class, $product, ['allow_extra_fields' => true])
                            ->add('name', TextType::class, array(
                                'constraints' => array(
                                    new NotBlank(),
                                    new Length(array('min' => 3, 'max' => 250)),
                                ),
                            ))
                            ->add('price', TextType::class, array(
                                'constraints' => array(
                                    new NotBlank(),
                                ),
                            ))
                            ->add('is_visible', RadioType::class, ['mapped' => false, 'required' => false])
                            ->add('description', TextareaType::class, ['required' => false])
                            ->add('productType', ChoiceType::class, array('choices' => $this->getAllProductTypes()))
                            ->add('sku', TextType::class, ['required' => false])
                            ->add('weight', TextType::class)
                            ->add('images', FileType::class, array('multiple' => true, 'required' => false, 'attr' => ['accept' => '.jpg, .png, .jpeg', 'class' => '', 'title' => '', 'mapped' => false]))
                            ->add('brand', TextType::class, ['required' => false])
                            ->add('discount', TextType::class, ['mapped' => true, 'required' => false])
                            ->add('discount_type', ChoiceType::class, ['mapped' => true, 'required' => false, 'choices' => $discountTypes])
                            ->add('sales_price', TextType::class, ['mapped' => false, 'required' => false])
                            ->add('trackInventory', CheckboxType::class, ['label' => 'Track Inventory','label_attr' => ['class' => 'wk-bam-switch-label']])
                            ->add('quantity', TextType::class, ['required' => false])
                            ->add('inventory_status', ChoiceType::class, ['mapped' => true, 'required' => false, 'choices' => $inventoryStatus, 'placeholder' => false])
                            ->add('commission_type', ChoiceType::class, array(
                                'choices' => [
                                    'Fixed' => 'fixed',
                                    'Percentage (%)' => 'percentage'
                                ],
                            ))
                            ->add('commission', TextType::class, [
                                'required' => false,
                                'constraints' => [
                                    new Callback([$this, 'validateCommission'])
                                ]
                            ])
                            ->getForm();
        return $form;
    }
    public function getProductFormVillum($product , $seller = null) {
        $formFactory = $this->container->get('form.factory');

        $discountTypes = [
            'fixed' => 'AMOUNT',
            'percentage' => 'PERCENT'
        ];

        $inventoryStatus = [
            'in_stock' => true,
            'out_of_stock' => false
        ];

        $bottle_size = [
            // bottle weight in kg.
            'Split (187.5ml)' =>  '0.3',
            'Half/Demi (375ml)' => '0.6',
            'Standard (750ml)' => '1.2',
            'Magnum (1.5L)' => '2.1',
            'Double Magnum/Jeroboam (3.0L)' => '4.8',
            'Rehoboam (4.5L)' => '7.3',
            'Imperial (6.0L)' => '11',
            'Salmanazar (9.0L)' => '14.5',
            'Balthazar (12.0L)' => '20',
            'Nebuchadnezzar (15.0L)' => '25',
            'Solomon (18.0L)' => '29',
            'Primat (27L)' => '40',
            'Midas (30L)' => '48',
        ];

        $condition = [
            "kitchen" => "Kitchen",
            "regular_basement" => "Regular Basement",
            "tempered_wine_cellar" => "Tempered Wine Cellar",
            "wine_cellar" => "Wine Cellar",
            "wine_fridge" => "Wine Fridge",
            "other" => "Other",
        ];

        $conditionSecond = [
            "high_fill" => "High fill",
            "into_neck" => "Into neck",
            "lower_mid-shoulder" => "Lower mid-shoulder",
            "mid_shoulder" => "Mid shoulder",
            "top_shoulder" => "Top shoulder",
        ];

        $conditionThird = [
            "damaged" => "Damaged",
            "perfect" => "Perfect",
            "severely_damaged" => "Severely Damaged",
            "slightly_damaged" => "Slightly Damaged",
            "slightly_scratched" => "Slightly Scratched",
            "very_good" => "Very Good",
        ];

        $conditionFourth = [
            "wine_shop" => "Wine Shop",
            "directly_from_winery" => "Directly from Winery",
            "subscription" => "Subscription",
            "importer" => "Importer",
            "gift" => "Gift",
            "inherited" => "Inherited",
            "other" => "Other",
        ];

        $form = $formFactory->createBuilder(FormType::class, $product, ['allow_extra_fields' => true])
                            ->add('name', TextType::class, array(
                                'constraints' => array(
                                    new NotBlank(),
                                    new Length(array('min' => 3, 'max' => 250)),
                                ),
                            ))
                            ->add('price', TextType::class, array(
                                'constraints' => array(
                                    new NotBlank(),
                                ),
                            ))
                            ->add('is_visible', RadioType::class, ['mapped' => false, 'required' => false])
                            ->add('description', TextareaType::class, ['required' => false])
                            ->add('productType', TextType::class, array('data' => $this->translate->trans('physical'),
                                'attr' => array('readonly' => true)
                            ))
                            ->add('trackInventory', CheckboxType::class, ['label' => 'Track Inventory','label_attr' => ['class' => 'wk-bam-switch-label']])
                            ->add('quantity', TextType::class, ['required' => false])
                            ->add('inventory_status', ChoiceType::class, ['mapped' => true, 'required' => false, 'choices' => $inventoryStatus, 'placeholder' => false])
                            ->add('sku', TextType::class, [
                                'required' => false,
                                'attr' => [
                                    'readonly' => true,
                                ]
                            ])
                            ->add('weight', TextType::class, [
                                'attr' => ['readonly' => true],
                            ])
                            ->add('images1', FileType::class, ['required' => isset($product['id']) && !empty($product['id']) ? false : true,
                                'attr' => ['accept' => '.jpg, .png, .jpeg, .heic, .heif', 'mapped' => false],
                                'constraints' => [
                                    new Image([
                                        'maxSize' => '11M',
                                        'maxSizeMessage' => 'Maximum supported file size is 10 MB'
                                    ])
                                ]
                            ])
                            ->add('images2', FileType::class, ['required' => isset($product['id']) && !empty($product['id']) ? false : true,
                                'attr' => ['accept' => '.jpg, .png, .jpeg, .heic, .heif', 'mapped' => false],
                                'constraints' => [
                                    new Image([
                                        'maxSize' => '11M',
                                        'maxSizeMessage' => 'Maximum supported file size is 10 MB'
                                    ])
                                ]
                            ])
                            ->add('images3', FileType::class, ['required' => isset($product['id']) && !empty($product['id']) ? false : true,
                                'attr' => ['accept' => '.jpg, .png, .jpeg, .heic, .heif', 'mapped' => false],
                                'constraints' => [
                                    new Image([
                                        'maxSize' => '11M',
                                        'maxSizeMessage' => 'Maximum supported file size is 10 MB'
                                    ])
                                ]
                            ])
                            ->add('images4', FileType::class, ['required' => isset($product['id']) && !empty($product['id']) ? false : true,
                                'attr' => ['accept' => '.jpg, .png, .jpeg, .heic, .heif', 'mapped' => false],
                                'constraints' => [
                                    new Image([
                                        'maxSize' => '11M',
                                        'maxSizeMessage' => 'Maximum supported file size is 10 MB'
                                    ])
                                ]
                            ])
                            ->add('images5', FileType::class, ['required' => isset($product['id']) && !empty($product['id']) ? false : true,
                                'attr' => ['accept' => '.jpg, .png, .jpeg, .heic, .heif', 'mapped' => false],
                                'constraints' => [
                                    new Image([
                                        'maxSize' => '11M',
                                        'maxSizeMessage' => 'Maximum supported file size is 10 MB'
                                    ])
                                ]
                            ])
                            ->add('images6', FileType::class, ['required' => false ,
                                'attr' => ['accept' => '.jpg, .png, .jpeg, .heic, .heif', 'mapped' => false],
                                'constraints' => [
                                    new Image([
                                        'maxSize' => '11M',
                                        'maxSizeMessage' => 'Maximum supported file size is 10 MB'
                                    ])
                                ]
                            ])
                            ->add('brand', TextType::class, ['required' => true])
                            ->add('condition1', ChoiceType::class, [
                                'choices' => $condition,
                                'placeholder' => "Select stored",
                                'required' => true
                            ])
                            ->add('condition2', ChoiceType::class, [
                                'choices' => $conditionSecond,
                                'placeholder' => "Select condition of the bottle",
                                'required' => true
                            ])
                            ->add('condition3', ChoiceType::class, [
                                'choices' => $conditionThird,
                                'placeholder' => "Select Filling level of the bottle",
                                'required' => true
                            ])
                            ->add('condition4', ChoiceType::class, [
                                'choices' => $conditionFourth,
                                'placeholder' => "Select source of supply",
                                'required' => true
                            ])
                            ->add('extradetails', TextareaType::class, array('attr' => array('rows' => 3), 'required' => false))
                            ->add('commission', TextType::class, ['required' => false])
                            ->add('discount', TextType::class, ['mapped' => true, 'required' => false])
                            ->add('discount_type', ChoiceType::class, ['mapped' => true, 'required' => false, 'choices' => $discountTypes])
                            ->add('sales_price', TextType::class, ['mapped' => false, 'required' => false])
                            //->add('trackInventory', CheckboxType::class, ['mapped' => false, 'label' => 'Track Inventory'])
                            //->add('quantity', TextType::class, ['required' => false, 'mapped' => false])
                            //->add('inStock', TextType::class, ['required' => false, 'mapped' => false])
                            ->add('commission_type', ChoiceType::class, array(
                                'choices' => [
                                    'Fixed' => 'fixed',
                                    'Percentage (%)' => 'percentage'
                                ],
                            ))
                            ->add('grape_varity', ChoiceType::class, array(
                                'choices' => $this->wineVarieties(),
                                'placeholder' => $this->translate->trans('select_grape_variety'),
                                'required' => false,
                            ))
                            ->add('vintage',TextType::class,['required' => true])
                            // ->add('awards',ChoiceType::class,array(
                            //     'choices' => $this->getAwards(),
                            //     'required' => false,
                            //     // 'multiple' => true,
                            //     'mapped' => false,
                            // ))
                            // ->add('awardsValue',ChoiceType::class,array(
                            //     'required' => false,
                            //     'multiple' => true,
                            // ))
                            ->add('BottleSize',ChoiceType::class, [
                                'choices' => $bottle_size,
                                'placeholder' => "Select Bottle Size",
                                'required' => false
                            ])
                            ->add('country', ChoiceType::class,array(
                                'choices' => $this->getCountry(),
                                'placeholder' => $this->translate->trans('select_country'),
                                'required' => true,
                            ))
                            ->add('region', ChoiceType::class,array(
                                'choices' => [],
                                'placeholder' => $this->translate->trans('select_a_region'),
                                'required' => true, 
                            ))
                            ->add('appellation', choiceType::class,array(
                                'choices' => [],
                                'placeholder' => $this->translate->trans('select_an_appellation'),
                                'required' => false
                            ))
                            ->add('classification',ChoiceType::class,array(
                                'choices' => [],
                                'placeholder' => $this->translate->trans('select_a_classification'),
                                'required' => false
                            ))
                            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                                $form = $event->getForm();
                                $data = $event->getData();
                                if (isset($data['country'])) {
                                    $selectedCountry = $data['country'];
                                    $regions = $this->getRegionsForCountry($selectedCountry);

                                    $classification = $this->getClassificationByCountry($selectedCountry);
                                    
                                    $form->add('region', ChoiceType::class, [
                                        'choices' => $regions,
                                        'placeholder' => 'Select a region',
                                        'required' => true,
                                    ]);        
                                    $form->add('classification', ChoiceType::class, [
                                        'choices' => $classification,
                                        'placeholder' => 'Select an classification',
                                        'required' => false,
                                    ]);
                                }
                                if(isset($data['region'])) {
                                    $selectedregion = $data['region'];
                                    $appellations = $this->getAppellationsForCountry($selectedregion);
                                    $form->add('appellation', ChoiceType::class, [
                                        'choices' => $appellations,
                                        'placeholder' => 'Select an appellation',
                                        'required' => false,
                                    ]);
                                }
                                
                            })
                            
                            ->getForm();

       
        return $form;
    }
    public function validateCommission($value, ExecutionContextInterface $context)
    {   
        $data = $context->getRoot()->getData();
        $commission_type = $data['commission_type'];
        if($commission_type == "percentage" && ($value < 0  || $value > 100)) {
            $context->buildViolation("Value must be between 0 and 100")
                ->atPath('commision')
                ->addViolation();
        }
        $price = $context->getRoot()->getData()['price'];
        if ($commission_type == "fixed" && $value > $price) {
            $context->buildViolation('Commission cannot  be greater than the price.')
                ->atPath('commission')
                ->addViolation();
        }

    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
    // Your form class

// ...
    public function getClassificationByCountry($country) {
        $classifications = [
            '' => [],
            'New Zealand' => ['','CO- Certified Origin'],
            'Georgia' => [],
            'United Kingdom' => ['',"English Quality Sparkling Wine PDO","English Regional Wine PGI","English Wine PDO","Welsh Wine PDO"],
            'Canada' => ['',"VQA - Vintners Quality Alliance"],
            'Croatia' => [],
            'Hungary'=> ['' ,"DHC - PDO / OEM","FN","OFJ / PGI"],
            "Romania" => ['',"DOC - CIB","DOC - CMD","DOC - CT","IGT - Indicatie Geografica Tipica"],
            'Luxembourg' => [ '', "AOC / AOP","Grand Premier Cru","Premier Cru","Vendanges Tardives","Vin classe","Vin de Glace","Vin de Paille"],
            'Bulgaria' => ['','PDO'],
            'Greece' => ['',"Epitrapezios Inos","OPAP","OPE","PDO","PGI","TSG","Topikos Inos","Varietal Wine"],
            'Lebanon' => [],
            'Slovenia' => [],
            'Chile' => ['',"Andes","Costa","DO","Entre Cordilleras","Gran Reserva","Reserva","Reserva Especial","Reserva Privada","Secano Interior"],
            'China' => [],
            'Argentina' => ['',"DOC","Gran Reserva","IG","Reserva","Reserva Especial"],
            'South Africa' => ['','WO - Wine of Origin'],
            'Portugal' => ['', "DOC / DOP","IGP / Vinho Regional","IPR","Vinho de Mesa"],
            'France' => ['', "1er Cru","1er Cru Superieur","1er Grand Cru Classe","1er Grand Cru Classe A","2eme Grand Cru Classe","3eme Grand Cru Classe","4eme Grand Cru Classe","5eme Grand Cru Classe","AOP / AOC","Cru Artisan","Cru Bourgeois","Cru Bourgeois Exceptionnel","Cru Bourgeois Superieur","Cru Classe","Grand Cru","Grand Cru Classe","IGP / Vin de Pays","Vin de France / Vin de Table"],
            'Spain' => [ '',  "DO Crianza","DO Gran Reserva", "DO Joven","DO Reserva","DOCa","DOCa Crianza","DOCa Gran Reserva","DOCa Joven","DOCa Reserva","DOP","DOP / DO de Pago","DOP / DO de Pago Calificado","DOQ",
            "VCIG - Vino de Calidad con Indicación Geográfica","VORS","VOS","Vino de Mesa","Vino de Pago","Vino de Tierra"],
            'Lebanon' => [],
            'Slovenia' => [],
            'Chile' => ["", "Andes","Costa","DO","Entre Cordilleras","Gran Reserva","Reserva","Reserva Especial","Reserva Privada","Secano Interior"],
            'China' => [],
            'Argentina' => ['', "DOC","Gran Reserva","IG","Reserva", "Reserva Especial"],
            'South Africa' => ['','WO - Wine of Origin'],
            'Portugal' => ['', "DOC / DOP","IGP / Vinho Regional","IPR","Vinho de Mesa"],
            'France' => [ '',"1er Cru","1er Cru Superieur","1er Grand Cru Classe","1er Grand Cru Classe A","2eme Grand Cru Classe","3eme Grand Cru Classe","4eme Grand Cru Classe","5eme Grand Cru Classe",
            "AOP / AOC","Cru Artisan","Cru Bourgeois","Cru Bourgeois Exceptionnel","Cru Bourgeois Superieur","Cru Classe","Grand Cru","Grand Cru Classe","IGP / Vin de Pays","Vin de France / Vin de Table"],
            'Spain' => ['',"DO Crianza","DO Gran Reserva","DO Joven","DO Reserva","DOCa","DOCa Crianza","DOCa Gran Reserva","DOCa Joven","DOCa Reserva","DOP","DOP / DO de Pago",
            "DOP / DO de Pago Calificado","DOQ","VCIG - Vino de Calidad cin Indicación Geográfica","VORS","VOS","Vino de Mesa","Vino de Pago","Vino de Tierra"],
            'Italy' => ['', "VSQ - Vino Spumante de Qualita", "DOCG", "DOCG Dolce Naturale", "DOCG Riserva","DOCG Superiore","DOCG Superiore Riserva","DOP / DOC",
                "DOP / DOC Riserva","DOP / DOC Superiore","DOP / DOC Superiore Riserva","Gran Selezione","IGT / IGP","VS - Vino Spumante","Vino Comune","Vino Territoriale", "Vino Varietale","Vino di Tavola"],
            'Austria' =>['',"Ausbruch","Auslese","Beerenauslese","DAC - Districtus Austriae Controllatus","DAC - Reserve","Eiswein", "Erste STK Lage","Federspiel","Gebietswein",
            "Große STK Lage","Kabinett","Landwein","Ortswein","Qualitätswein","Riedenwein","Sekt - Große Reserve","Sekt - Klassik","Sekt - Reserve","Smaragd","Spätlese","Steinfeder","Strohwein / Schilfwein","Trockenbeerenauslese","ÖTW Erste Lage"],
            'Germany' =>[ '',"Auslese","Beerenauslese","Deutscher Wein / Tafelwein","Eiswein","Erstes Gewächs","Kabinett","Landwein","QbA - Qualitätswein","QmP- Prädikatswein","Sekt","Spätlese","Trockenbeerenauslese","VDP. Aus Ersten Lagen","VDP. Erste Lage","VDP. Große Lage","VDP. Großes Gewächs","VDP. Gutswein","VDP. Ortswein"],
            'USA' => [ '',"AVA (American Viticultural Area)","County Appellation","State Appellation"],
            'Switzerland' => ['', "AOC / DOC (Appellation d'Origine Contrôlée / Denominazione di Origine Controllata","Grand Cru","Premier Cru","Premier Grand Cru","Vin de Pays"],
            'Australia' => ['','GI - Geographical Indication'],
            'New Zealand' => ['','CO- Certified Origin'],
        ];
        asort($classifications);

        if(isset($classifications[$country])) {
            $classification = $classifications[$country];

            $classificationchoice = array_combine($classification,$classification);
            return $classificationchoice;
        } else {
            return [];
        }
    }
    public function getAwards() {
        $awards = [
            // 'Robert Parker: 0-100' => 'Robert Parker: 0-100',
            // 'James Suckling: 0-100' => 'James Suckling: 0-100',
            // 'Jancis Robinson: 0-20' => 'Jancis Robinson: 0-20',
            // 'Wine Spectator: 0-100' => 'Wine Spectator: 0-100',
            // 'Antonio Galloni: 0-100' => 'Antonio Galloni: 0-100',
            // 'René Gabriel: 0-20' => 'René Gabriel: 0-20'
            'Antonio Galloni' => 'Antonio Galloni',
            'James Suckling' => 'James Suckling',
            'Jancis Robinson' => 'Jancis Robinson',
            'René Gabriel' => 'René Gabriel',
            'Robert Parker' => 'Robert Parker',
            'Wine Spectator' => 'Wine Spectator',
        ];
        return $awards;
    }
    public function getRegionsForCountry($country) {
        
        $regioncountry =[
            '' => [],
            'New Zealand'=> ['','Auckland', 'Auckland', 'Canterbury / Waipara Valley', 'Cental Otago', 
            'Gisborne', 'Hawke´s Bay', 'Marlborogh', 'Martinborough', 'Nelson', 'Northland', 'Waikato', 'Wairarapa', 'Waitaki Valley'],
            'Austria' => ['','Bergland', 'Bodensee-Vorarlberg', 'Burgenland', 'Burgenland', 'Niederösterreich (Lower Austria)','Steiermark (Styria)','Wien (Vienna)'],
            'Germany' => ['', 'Ahr', 'Baden', 'Franken', 'Hessische Bergstrasse', 'Mittelrhein', 'Mosel', 'Nahe', 'Pfalz', 'Rheingau', 'Rheinhessen', 'Saale-Urstut', 'Sachsen', 'Schleswig Holstein', 'Württemberg'],
            'Switzerland' => ['','Appenzell','Argovie','Basel','Berne','Fribourg','Geneve','Glaris','Graubünden','Jura','Luzern','Neuchatel','Nidwald','Obwald','Romandie','Schaffhouse','Schwyz','St. Gallen','Thurgau','Ticino','Uri','Valais','Vaud','Zug','Zürich'],
            'South Africa' => ['','Eastern Cape', 'Kwazulu-Natal', 'Limpopo', 'Nothern Cape', 'Western Cape'],
            'China' => ['','Gansu', 'Hebei', 'Heilongjiang', 'Henan', 'Jilin', 'Liaoning', 'Ningxia', 'Shandong', 'Shanxi', 'Tianjin', 'Xinjiang', 'Yunnan'],
            'Italy' => ['','Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia Romagna', 'Friaul-Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche', 'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana' ,'Trentino Alto-Adige', 'Umbria', 'Valle d´Aosta', 'Veneto'],
            'France' => ['','Alsace', 'Auvergne', 'Beaujolais', 'Bordeaux', 'Burgund', 'Bretagne', 'Champagne', 'Corse', 'Ile de France', 'Jura', 'Languedoc-Roussillion', 'Lorraine', 'Nord', 'Normandie', 'Outre-Mer', 'Cognac', 'Provence', 'Savoie', 'Sud-Oest', 'Vallee de la Loire', 'Vallee du Rhone', 'Vosges'],
            'Australia' => ['','New South Wales', 'Queensland', 'South Australia', 'South Eastern Australia', 'Tasmania', 'Victoria', 'Western Australia'],
            'Argentina' => ['','Buenos Aires', 'Catamarca', 'Chubut', 'Cordoba', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Neuquen', 'Patagonia', 'Rio Negro', 'Salta', 'San Juan', 'Tucuman'],
            'Luxembourg' => ['','Moselle Luxembourgeoise'],
            'Bulgaria' => ['','Black Sea', 'Danubian Plain', 'Rose Valley', 'Struma Valley', 'Thracian Valley'],
            'Greece' => ['','Agean Islands', 'Epirus', 'Ionian Islands', 'Kreta', 'Makedonia', 'Peloponnes', 'Sterea Ellada / Central Greece', 'Thessalia', 'Thraki'],
            'Romania' => ['','Banat', 'Colinele Dobrogei', 'Crisana', 'Danube Terraces', 'Donbrudja', 'Moldavia', 'Muntenia', 'Oltenia', 'Transylvania'],
            'USA' => ['','Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana', 'Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee', 'Texas','Utah','Vermont','Virginia','Washington','West Virginia', 'Wisconsin','Wyoming'],
            'Spain' => ['Andalucia', 'Aragon', 'Asturias', 'Baleares', 'Canarias', 'Cantabria', 'Castilla La Mancha', 'Castilla y Leon', 'Cataluna', 'Extremadura', 'Galicia', 'Madrid','La Rioja','Madrid','Murcia','Navarra','Pais Vasco','Valencia'],
            'Portugal' => ['','Alentejo','Algarve', 'Acores', 'Beira Atalntico','Beira Interior', 'Douro','Dao', 'Lisboa', 'Madeira','Minho','Setubal','Tavora e Varosa','Tejo','Tras-os-Montes'],
            'Chile' => ['','Aconcagua','Atacama','Austral','Central Valley','Coquimbo','Southern Chile'],
            'Slovenia' => ['','Podravje (Sava Valley)','Podravje (Lower Sava Valley)','Primorska (Littoral)'],
            'Lebanon' => ['','Bekaa Valley'],
            'Croatia' => ['','Coastal Croatia','Continental Croatia','Istria'],
            'Hungary' => ['','Balaton','Duna- The great Hungarian plain (Alföld)','Del-Pannonia (South Pannonia)','Felso-Magyarorszag (Hegyvidek)','Tokaj','Eszak-Dunantul (North-Transdanubia)'],
            'England' => ['','Campbeltown (Scotland)','Cornwall (England)','Devon (England)','Dorset (England','EastAnglia €','Gloucestershire (Eng.)','Hampshire (Eng.)','Herefordshire (Eng.)','Highland ( Scot.)','Island (Scot.)', 'Islay (Scot.)','Isle of Arran (Eng.)','Isle of Wight (Eng.)','Isle of Scilly (Eng.)','Jersey (Eng.)','Kent (Eng.)','Lincolnshire (Eng.)', 'London ', 'Lowland (Scot.)','Northhamptonshire (Eng.)','Oxfordshire (Eng.)','Shropshire (Eng.)','Somerset (Eng.)','Speyside ( Scot.)','Surrey (Eng.)','Sussex (Eng.)','Wales (Wales)', 'Worcestershire (Eng.)','Yorkshire (Eng.)'],
            'Georgia' => ['','Black Sea Coastal Zone', 'Imereti', 'Kakheti', 'Kartli', 'Meskheti','Racha-Lechkhumi / Kvemo Svaneti'],
            'Canada' => ['','British Columbia','Newfoundland','Nova Scotia','Ontario','Quebec'],
        ];
        asort($regioncountry);

        if(isset($regioncountry[$country])) {
            $regions = $regioncountry[$country];

            $regionChoices =array_combine($regions,$regions);
            
            return $regionChoices;
        } else {
            return [];
        }
    }
    public function getAppellationsForCountry($regions) {

        $appellationbyregion = [
            'New South Wales' => ['',"Big Rivers","Broke Fordwich","Canberra","Central Ranges","Cowra","Gundagai","Hastings River","Hilltops","Hunter Valley","Lower Hunter Valley","Mudgee",
            "Murray Darling","New England","Northern Rivers","Northern Slopes","Orange","Perricoota","Pokolbin","Riverina","Shoalhaven Coast","Southern Highlands","Southern New South Wales","Swan Hill","Tumbarumba","Upper Hunter Valley","Western Plains"],
            'Auckland' => ['',"Auckland","Aukland","Henderson","Kumeu","Matakana","Waiheke Island","West Auckland"],
            'Canterbury / Waipara Valley' =>  ["Canterbury / Waipara Valley","CaterburCanterbury","North Canterbury","Waipara"],
            'Cental' => ['',"Alexandra","Bannockburn","Bendigo","Central Otago","Cromwell","Gibbston","Lowburn / Pisa","Wanaka"],
            'Gisborne' => ['',"Gisborne","Manutuke","Ormond","Patutahi"],
            "Hawke´s Bay" =>['', 'Hawke´s Bay','Bridge Pa','Central Hawke´s Bay','Eskdale','Gimblett Gravels','Havelock North','Hawke´s Bay','Korokipo','Maraekakaho','Meanee','Ohiti','Taradale','Te Awanga',],
            "Marlborogh"=> ['', 'Marlborough','Awatere Valley','Blenheim','Cloudy Bay','Marlborough','Renwick','Seddon','Spring Creek','Waihopai','Wairau Valley'],
            "Martinborough"  => [],
            "Nelson" => ['', 'Nelson','Moutere Hills','Nelson','Waimere Plains'],
            "Northland" => ['',"Northland"],
            "Waikato" => [],
            "Wairarapa" => [ '', 'Gladstone','Martinborough','Masterton','Wairarapa',],
            "Waitaki Valley" => ['','Waitaki Valley'],
            'Queensland' => ['','Granite Belt', 'South Burnett'],
            'South Australia' => [ '','Adelaide Hills','Adelaide Plains','Barossa Valley','Clare Valley','Coonawarra','Currency Creek','Eden Valley','Far North','Fleurieu','High Eden',
            'Kangaroo Island','Langhorne Creek','Lenswood','Limestone Coast','McLaren Vale','Mount Benson','Mount Lofty Ranges','Padthaway','Peninsulas','Piccadilly Valley','Ranges','Riverland','Robe','Southern Fleurieu','Southern Flinders Range','Wrattonbully',],
            'South Eastern Australia' => [],
            'Tasmania' => [ '','Coal River','Derwent Valley','East Coast','North West','Pipers River','Southern','Tamar Valley','Tasmania',],
            'Victoria' => [ '','Victoria','Alpine Valleys','Beechworth','Bendigo','Geelong','Gippsland','Glenrowan','Goulburn Valley','Grampians','Heathcote','Henty','King Valley','Macedon Ranges','Mornington Peninsula','Murray Darling','Nagambie','Port Phillip','Pyrenees','Rutherglen','Strathbogie Ranges','Sunbury','Swan Hill','Upper Goulburn',],
            'Western Australia' => ['','Western Australia','Blackwood Valley','Geographe','Great Southern','Greater Perth', 'Manjimup', 'Margaret River','Peel','Pemberton','Perth Hills','South Western Australia','Swan Valley','Wilyabrup',],
            'Appenzell' => ['','Appenzell','Oberegg',' Wienacht-Tobel'],
            'Argovie' => [  '','Arau','Aargau','Baden','Birmenstorf','Bremgarten','Bözberg','Fricktal','Hallwilersee','Klingnau','Obersiggenthal','Remingen','Schniznach','Tegerfelden','Untersiggenthal','Villigen',],
            'Basel' => [    '','Basel','Basel-Landschaft','Basel-Stadt','Nordwestschweiz',],
            'Berne' => [  '','Berne','Bielersee / Lac de Bienne', 'Erlach','La Neuveville', 'Ligerz','Schafiser / Schafis','Thunersee','Schugg','Twann','Tüscherz','Vigneules',],
            'Fribourg' => ['','Broye','Cheyres','Vully'],
            'Geneve' => ['','Bardonnex','Chateau de Choully','Chateau du Crest','Cologny','Confignon','Coteau de Bossy','Coteau de Bourdigny','Coteau de Chevrens','Coteau de Choulex','Coteau de Choully','Coteau de Genthod','Coteau de Lully','Coteau de Peissy','Coteau de la vigne blanche','Coteau de Baillets','Coteaux de Dardagny','Coteaux de Meinier','Coteaux de Peney', 'Celigny','Cotes de Landecy','Cotes de Russin','Domaine de l´Abbaye','Geneve','Grand Carraz','Hermance','La Feuillee','Laconnex','Mandement de Jussy','Rougemont','Satigny',],
            'Glaris' => ['','Glaris','Glarus','Mollis','Niederurnen'],
            'Graubünden' => [ '','Graubünden', 'Fläsch','Jenis','Maienfeld','Malans'],
            'Jura' => ['','Buix'],
            'Luzern' => ['','Baldeggersee','Luzern','Sempachersee',],
            'Neuchatel' => [ '','Neuchatel','Auvernier','Bevaix','Boudry','Bole','Chmapreveyres','Chez le Bart','Colombier','Corcelles-Cormondreche','Cornaux','Cortaillod','Cressier','Fresens','Gorgier','Hauterive','La Coudre','Le Landeron','Peseux','Saint-Aubin-Sauges','Saint-Blaise','Vaumarcus','Vin de Pays Romand',],
            'Nidwald' => [''],
            'Obwald' => ['','Obwalden'],
            'Romandie' => [],
            'Schaffhouse' => ['','Schaffhouse','Aarau','Baden','Brugg','Dietikon','Einsiedeln','Freienbach','Horgen','Knonau','Lenzburg','Liestal','Menziken','Muri','Rapperswil-Jona','Schinznach','Steinhausen','Thalwil','Uster','Wädenswil','Wil','Winterthur','Zug'],
            'Schwyz' => ['','Schwyz','Buchen','Eschenbach','Freienbach','Gersau','Höfe','Küssnacht','Lachen','March','Münchwilen','Schwyz','Siglistorf','Steinhausen','Thalwil','Uster','Wädenswil','Wil','Winterthur','Zug'],
            'Solothurn' => ['','Dornach','Flüh','Solothurn'],
            'St. Gallen' => ['','Berneck','Heerbrugg','Rapperwil','Rheintal','St. Gallen'],
            'Thurgau' => ['','Dettinghof','Diessenhofen','Ermatingen','Frauenfeld','Herden','Hüttwilen','Nussbaum','Salenstein','Schlattingen'],
            'Ticino' => ['','Bianco del Ticino','Biasca','Castel San Pietro','Chiasso','Giornico','Giubiasco','Gordola','Gudo','Malvaglia','Morbio','Morcote','Pedrinate','Rivera','Rovio','Stabio','Tenero','Ticino','Verscio'],
            'Uri' => ['','Bürglen','Uri'],
            'Valais' => [  '','Ardon','Ayent', 'Chamoson','Conthey','Coteaux de Sierre','Fully','Grimisuat','Lens','Leytron','Martigny','Miège','Saillon','Salquenen','Savièse','Saxon','Sion','Valais','Varen','Venthône','Vétroz',],
            'Vaud' => [ '','Vaud','Aigle','Allaman','Arnex sur Orbe','Aubonne','Begnins','Bex','Bonvillars','Bursinel','Calamin','Chablais','Chardonne','Coteau de Vincy','Cotes de lOrbe','Dezaley','Dezaley-Marsens', 'Epesses','Fechy','La Côte','Lavaux','Lonay','Luins','Lutry','Mont-sur-Rolle','Morges','Nyon','Ollon','Perroy','Saphorin','Signy-Avenex','Tartegnin','Vaud','Vevey-Montreux','Villeneuve','Villette','Vinzel','Vully','Yvorne'],
            'Zug' => ['','Zug','Hünenberg','Walchwil','Zug'],
            'Zürich' => [ '','Zürich','Andelfingen','Au','Dachsen','Eglisau','Flaach','Meilen','Neftenbach','Oberstammheim','Seuzach','Stäfa','Unterstammheim','Weiningen','Wädenswil','Zürichsee'],
            'Alabama' => ['','Alabama'],
            'Alaska' => [],
            'Arizona' => [ '','Arizona','Sonoita','Verde Valley','Willcox'],
            'Arkansas' => ['','Altus','Arkansas','Arkansas Mountain','Ozark Mountain'],
            'California' => [  '','California','Adelaida District','Chalone','Alexander Valley','Alta Mesa','Amador County','Anderson Valley','Antelope Valley','Arroyo Grande Valley','Arroyo Seco','Atlas Peak','Ballard Canyon','Ben Lomond Mountain','Benmore Valley','Bennett Valley','Big Valley District','Borden Ranch','Calaveras County','California Shenandoah Valley','Calistoga','Capay Valley','Camel Valley','Carneros','Central Coast','Chalk Hill','Chiles Valley','Cienega Valley','Clarksburg','Clear Lake','Clements Hills', 'Cole Ranch','Contra Costa County','Coombsville','Cosumnes River','Covelo','Creston District','Cucamonga Valley','Diablo Grande','Diamond Mountain District','Dos Rios','Dry Creek Valley','Dunnigan Hills',
            'Eagle Peak Mendocino County','Edna Valley','El Dorado','El Dorado County','El Pomar District','Fair Play','Fiddletown','Fort Ross-Seaview','Fountaingrove District','Fresno County','Green Valley of Russian River Valley','Guenoc Valley',
            'Hames Valley','Happy Canyon of Santa Barbara','High Valley','Howell Mountain','Humboldt County','Jahant','Kelsey Bench-Lake County','Knights Valley','Lake County','Leona Valley','Lime Kiln Valley','Livermore Valley','Lodi','Los Angeles County',
            'Los Olivos District','Madera','Malibu Coast','Malibu-Newton Canyon','Manton Valley','Marin County','Mariposa County','McDowell Valley','Mendocino','Mendocino County','Mendocino Ridge','Merritt Island','Mokelumne River','Monterey','Monterey County','Moon Mountain District','Mt. Harlan','Mt. Veeder','Napa County','Napa Valley','Nevada County','North Coast','North Yuba', 'Northern Sonoma','Oak Knoll District','Oakville','Pacheco Pass',
            'Paicines','Paso Robles','Paso Robles Estrella District','Paso Robles Geneseo District','Paso Robles Highland District','Paso Robles Willow Creek District','Petaluma Gap','Pine Mountain-Cloverdale','Placer County','Potter Valley','Ramona Valley','Red Hills Lake County','Redwood Valley','River Junction','Rockpile','Russian River Valley','Rutherford','Saddle Rock-Malibu','Saint Helena','Salado Creek','San Antonio Valley','San Benito','San Benito County','San Bernabe','San Diego County','San Francisco Bay','San Joaquin County','San Juan Creek','San Lucas','San Luis Obispo County','San Miguel District','San Pasqual Valley','San Ysidro District','Santa Barbara County','Santa Clara Valley','Santa Clara County','Santa Cruz Mountains','Santa Lucia Highlands','Santa Margarita Ranch','Santa Maria Valley','Santa Ynez Valley','Seiad Valley','Sierra Foothills','Sierra Pelona Valley','Sloughhouse','Solano County','Solano County Green Valley','Sonoma Coast','Sonoma County','Sonoma Mountain','Sonoma Valley','South Coast','Spring Mountain District','Squaw Valley-Miramonte','Sta. Rita Hills','Stags Leap District','Suisun Valley','Tehachapi-Cummings Valley','Temecula Valley','Templeton Gap District','Tracy Hills','Trinity Lakes','Trinity County','Tuolumne County','Ventura County','Wild Horse Valley','Willow Creek','Yolo County','York Mountain','Yorkville Highlands','Yountville',],
            'Colorado' => ['','Grand Valley','West Elks'],
            'Connecticut' => ['','Connecticut','Southeastern New England','Western Connecticut Highland'],
            'Delaware' => ['','Delaware'],
            'Florida' => ['','Florida'],
            'Georgia' => ['','Georgia','Lumpkin County'],
            'Hawaii' => ['','Hawaii'],
            'Idaho' => ['','Idaho', 'Lewis-Clark Valley', 'Snake River Valley'],
            'Illinois' => ['','Shawnee Hills', 'Upper Mississippi River Valley'],
            'Indiana' => ['','Ohio River Valley'],
            'Iowa' => ['','Iowa', 'Upper Mississippi River Valley'],
            'Kansas' => ['','Kansas'],
            'Kentucky' => ['','Ohio River Valley'],
            'Louisiana' => ['','Louisiana', 'Mississippi Delta'],
            'Maine' => ['','Maine'],
            'Maryland' => ['','Catoctin','Cumberland Valley', 'Linganore', 'Maryland'],
            'Massachusetts' => ['','Martha\'s Vineyard', 'Massachusetts', 'Southeastern New England'],
            'Michigan' => ['','Fennville', 'Lake Michigan Shore', 'Leelanau Peninsula', 'Michigan', 'Old Mission Peninsula'],
            'Minnesota' => ['','Alexandria Lakes', 'Minnesota', 'Upper Mississippi River Valley'],
            'Mississippi' => ['','Mississippi', 'Mississippi', 'Mississippi Delta'],
            'Missouri' => ['','Augusta', 'Hermann', 'Missouri', 'Ozark Highlands', 'Ozark Mountain'],
            'Montana' => ['','Montana'],
            'Nebraska' => ['','Nebraska'],
            'Nevada' => ['','Nevada'],
            'New Hampshire' => ['','New Hampshire'],
            'New Jersey' => [ '','Central Delaware Valley', 'New Jersey', 'Outer Coastal Plain', 'Warren Hills'],
            'New Mexico' => ['','Mesilla Valley', 'Middle Rio Grande Valley', 'Mimbres Valley', 'New Mexico'],
            'New York' => ['','Cayuga Lake', 'Finger Lakes', 'Hudson River Region', 'Lake Erie', 'Long Island', 'New York', 'Niagara County', 'Niagara Escarpment', 'North Fork of Long Island', 'Seneca Lake', 'The Hamptons, Long Island'],
            'North Carolina' => ['','Haw River Valley', 'North Carolina', 'Swan Creek', 'Yadkin Valley'],
            'North Dakota' => ['','North Dakota'],
            'Ohio' => ['','Grand River Valley', 'Isle St. George', 'Kanawha River Valley', 'Lake Erie', 'Loramie Creek', 'Ohio', 'Ohio River Valley'],
            'Oklahoma' => ['','Oklahoma', 'Ozark Mountain'],
            'Oregon' => ['',
                'Applegate Valley', 'Chehalem Mountains', 'Columbia Gorge', 'Columbia Valley', 'Dundee Hills',
                'Elkton Oregon', 'Eola Amity Hills', 'Hood River County', 'McMinnville', 'Oregon', 'Polk County',
                'Red Hill Douglas County', 'Ribbon Ridge', 'Rouge Valley', 'Snake River Valley', 'Southern Oregon',
                'The Rocks District of Milton-Freewater', 'Umpqua Valley', 'Van Duzer Corridor', 'Walla Walla Valley'
            ],
            'Pennsylvania' => ['','Pennsylvania', 'Brandywine Valley', 'Central Delaware Valley', 'Cumberland Valley', 'Lake Erie', 'Lancaster Valley', 'Lehigh Valley', 'Pennsylvania'],
            'Rhode Island' => ['','Rhode Island', 'Southeastern New England'],
            'South Carolina' => ['','South Carolina'],
            'South Dakota' => ['','South Dakota'],
            'Tennessee' => ['','Mississippi Delta', 'Tennessee'],
            'Texas' =>  ['','Texas', 'Bell Mountain', 'Escondido Valley', 'Fredericksburg in the Texas Hill Country', 'Lubbock County', 'Mesilla Valley', 'Texas', 'Texas Davis Mountains', 'Texas High Plains', 'Texas Hill Country', 'Texoma'],
            'Utah' => ['','Utah'],
            'Vermont' => ['','Vermont'],
            'Virginia' =>  ['','Loudoun County', 'Monticello', 'North Fork of Roanoke', 'Northern Neck George Washington Birthplace','Orange County', 'Rocky Knob', 'Shenandoah Valley', 'Virginia', 'Virginia\'s Eastern Shore'],
            'Washington' =>  ['','Ancient Lakes of Columbia Valley', 'Columbia Gorge', 'Columbia Valley', 'Horse Heaven Hills','Lake Chelan', 'Lewis-Clark Valley', 'Naches Heights', 'Puget Sound', 'Rattlesnake Hills','Red Mountain', 'Royal Slope', 'Snipes Mountain', 'The Rocks District of Milton-Freewater','Wahluke Slope', 'Walla Walla Valley', 'Washington', 'Yakima Valley'],
            'West Virginia' => ['','Kanawha River Valley', 'Ohio River Valley', 'Shenandoah Valley', 'West Virginia'],
            'Wisconsin' => ['','Lake Wisconsin', 'Upper Mississippi River Valley', 'Wisconsin'],
            'Wyoming' => ['','Wyoming'],
            'Ahr' => ['','Landwein Ahrtaler','Walporzheim'],
            'Baden' => ['','Badische Bergstrasse', 'Bodensee', 'Breisgau', 'Kaiserstuhl', 'Kraichgau','Landwein Taubertäler', 'Landwein Unterbadischer', 'Markgräflerland', 'Ortenau','Südbadischer', 'Tauberfranken', 'Tuniberg'],
            'Franken' => ['','Burgstadt', 'Landwein Fränkischer', 'Landwein Regensbruger', 'Maindreieck', 'Mainviereck', 'Steigerwald', 'Tauberfranken'],
            'Hessische Bergstrasse' => ['','Landwein Starkenburger', 'Starkenburg', 'Umstadt'],
            'Mittelrhein' => ['','Landwein Rheinburgen', 'Loreley', 'Siebengebirge'],
            'Mosel' => ['','Bernkastel', 'Burg Cochem', 'Landwein Saarländischer', 'Landwein der Mosel','Landwein der Ruwer', 'Moseltor', 'Obermosel', 'Ruwertal'],
            'Nahe' => ['','Landwein Nahegauer', 'Nahetal'],
            'Pfalz' => ['','Landwein Pfälzer', 'Mittelhaardt-Deutsche Weinstrasse', 'Südliche Weinstrasse'],
            'Rheingau' => ['','Johannisberg', 'Landwein Altrheingauer'],
            'Rheinhessen' => ['','Bingen', 'Landwein Rheinischer', 'Nierstein', 'Wonnegau'],
            'Saale-Urstut' => ['','Landwein Mitteldeutscher', 'Mansfelder Seen', 'Schlossneuenburg', 'Thüringen'],
            'Sachsen' => ['','Dresden', 'Elstertal', 'Landwein Mecklenburger', 'Landwein Sächsischer', 'Meissen'],
            'Schleswig Holstein' => [],
            'Württemberg' => ['','Bayerischer Bodensee', 'Kocher-Jagst-Tauber', 'Landwein Bayerischer Bodensee','Landwein Schwäbischer', 'Oberer Neckar', 'Remstal-Stuttgart', 'Württembergisch Bodensee','Württembergisch Unterland'],
            'Bergland' => ['','Kärnten', 'Oberösterreich', 'Salzburg', 'Tirol'],
            'Bodensee-Vorarlberg' => [],
            'Burgenland' => ['','Eisenberg', 'Leithaberg', 'Mittelburgenland', 'Neusiedlersee','Neusiedlersee-Hügelland', 'Rosalia', 'Ruster Ausbruch', 'Südburgenland'],
            'Niederösterreich (Lower Austria)' => ['','Carnuntum', 'Kamptal', 'Kremstal', 'Thermenregion', 'Traisental', 'Wachau', 'Wagram', 'Weinviertel'],
            'Steiermark (Styria)' => ['Südsteiermark', 'Vulkanland Steiermark', 'Weststeiermark'],
            'Wien (Vienna)' => [],
            'Abruzzo' => ['','Alto Tirino', 'Cerasuolo dAbruzzo', 'Colli Aprutini', 'Colli del Sangro','Colline Frentane', 'Colline Pescaresi', 'Colline Teatine', 'Controguerra',
                'Del Vastese / Histonium', 'Montepulciano dAbruzzo', 'Ortona', 'Terre Aquilane',
                'Terre Tollesi', 'Terre di Chieti', 'Trebbiano dAbruzzo', 'Valle Peligna', 'Villamagna'
            ],
            'Basilicata' => ['','Aglianico del Vulture', 'Basilicata', 'Grottino di Roccanova', 'Matera', 'Terre dell Alta Val dAgri'],
            'Calabria' => ['','Arghilla', 'Bigonvi', 'Calabria', 'Ciro', 'Costa Viola', 'Greco di Bianco','Lamezia', 'Lipuda', 'Locride', 'Melissa', 'Palizzi', 'Pellaro','San´t Anna di Isola Capo Rizzuto', 'Savuto', 'Scavigna', 'Scilla','Terre di Cosenza', 'Val di Neto', 'Valdamato'],
            'Campania' => ['','Aglianico del Taburno', 'Aversa', 'Beneventano', 'Campania', 'Campi Flegrei','Capri', 'Casavecchia di Pontelatone', 'Castel San Lorenzo', 'Catalanesca del Monte Somma','Cliento', 'Colli di Salerno', 'Costa d´Amalfi', 'Dugenta', 'Epomeo', 'Falanghina del Sannio','Falerno del Massico', 'Fiano di Avellino', 'Galluccio', 'Greco Campania', 'Greco di Tufo','Irpinia', 'Ischia', 'Lacrima Christi del Vesuvio', 'Paestum', 'Penisola Sorrentina', 'Pompeiano','Roccamonfina', 'Sannio', 'Taurasi', 'Terre del Volturno', 'Vesuvio'],
            'Emilia Romagna' => ['','Bianco di Castelfranco Emilia', 'Bosco Eliceo', 'Colli Bolognesi', 'Colli Bolognesi Classico Pignoletto','Colli Bolognesi Pignoletto', 'Colli Piacentini', 'Colli Romagna Centrale', 'Colli d´Imola', 'Colli di Faenza','Colli di Parma', 'Colli di Rimini', 'Colli di Scandiano e di Canossa', 'Emilia', 'Forli', 'Fontana del Taro','Gutturnio', 'Lambrusco Grasparossa di Castelvetro', 'Lambrusco Salamino di Santa Croce', 'Lambrusco di Sorbara','Modena', 'Ortrugo dei Colli Piacentini', 'Ravenna', 'Reggiano', 'Reno', 'Romagna', 'Sillaro', 'Terre di Veleja','Val Tidone'],
            'Friaul-Venezia Giulia' => ['','Alto Livenza', 'Carso', 'Carso Classico', 'Colli Orientali del Friuli', 'Collio', 'Collio Goriziano','Delle Venezie', 'Friuli', 'Friuli Annia', 'Friuli Aquileia', 'Friuli Colli Orientali', 'Friuli Grave','Griuli Isonzo', 'Friuli Latisana', 'Lison', 'Lison Classico', 'Lison Pramaggiore', 'Prosecco', 'Prosecco Trieste','Ramandolo', 'Rosazzo', 'Trevenezie', 'Venezia Giulia'],
            'Lazio' => ['','Aleatico di Gradoli', 'Anagni', 'Aprilla', 'Atina', 'Bianco Capena', 'Cannellino di Frascati','Castelli Romani', 'Cervereti', 'Cesanese del Piglio', 'Cesanese di Affile', 'Cesanese di Olevano Romano','Circeo', 'Civitella d Agliano', 'Colli Albani', 'Colli Cimini', 'Colli Etruschi Vitebesi', 'Colli Lanuvini','Colli della Sabina', 'Cori', 'Costa Etrusco Romana', 'Frascati', 'Frusinate', 'Genazzano', 'Lazio', 'Marino','Montecompatri Colonna', 'Nettuno', 'Orvieto', 'Roma', 'Tarquinia', 'Terracina', 'Velletri', 'Vignanello','Zagarolo'],
            'Liguria' => ['','Cinque Terre', 'Cinque terre Sciacchetra', 'Colli di Luni', 'Colline Savonesi', 'Colline del Genovesato','Colline di Levanto', 'Golfo die Poeti', 'Golfo del Tigullio', 'Golfo del Tigullio-Portofino o Portofino','Liguria di Levante', 'Pornassio', 'Rivera Liguria di Ponente', 'Rossesse di Doleacqua', 'Terrazze dell Imperiese','Val Polcevera'],
            'Lombardia' =>  ['','Alpi Retiche', 'Alto Mincio', 'Benaco Bresciano', 'Bergamasca', 'Bonarda dell´Oltrepo Pavese','Botticino', 'Buttafuoco dell´Oltrepo Pavese', 'Capriano del Colle', 'Casteggio', 'Cellatica','Collina del Milanese', 'Curtefranca', 'Franciacorta', 'Garda', 'Garda Colli Mantovani','Lambrusco Mantovano', 'Lugana', 'Montenetto di Brescia', 'Moscato di Scanzo', 'Oltrepo Pavese','Oltrepo Pavese Metodo Classico', 'Provincia di Mantova', 'Provincia di Pavia', 'Quistello','Riviera del Garda Classico', 'Ronchi Varesini', 'Ronchi di Brescia', 'Sabbioneta', 'San Colombano al Lambro','San Martino della Battaglia', 'Sangue di Giuda', 'Sebino', 'Sforzato di Valtellina', 'Terrazze Retiche di Sondrio','Terre Lariane', 'Terre del Colleoni', 'Valcamonica', 'Valcalepio', 'Valtellina', 'Valtenesi'],
            'Marche' =>  ['','Bianchello del Metauro', 'Castelli di Jesi Verdicchio', 'Castelli di Jesi Verdicchio Classico', 'Colli Maceratesi','Colli Pesaresi', 'Conero', 'Esino', 'Falerio', 'I Terreni di San Severino', 'Lacrima di Morro d´Alba','Marche', 'Offida', 'Pergola', 'Rosso Conero', 'Rosso Piceno', 'San Ginesio', 'Serrapetrona', 'Terre di Offida','Verdicchio del Castelli di Jesi', 'Verdicchio del Castelli di Jesi Classico', 'Verdicchio die Matelica','Vernaccia di Serrapetrona'],
            'Molise' => ['','Biferno', 'Molise', 'Osco', 'Pentro d´Isernia', 'Rotae', 'Terre degli Osci', 'Tintilla del Molise'],
            'Piemonte' => ['',
                'Alba', 'Albugnano', 'Alta Langa', 'Asti', 'Barbaresco', 'Barbera d´Alba', 'Barbera d´Asti', 'Barbera del Monferrato',
                'Barolo', 'Boca', 'Brachetto d´Acqui', 'Bramaterra', 'Calosso', 'Caluso Passito', 'Canavese', 'Carema',
                'Cisterna d´Asti', 'Colli Saluzzesi', 'Colli Tortonesi', 'Collina Torinese', 'Colline Novaresi',
                'Cortese dell´Alto Monferrato', 'Coste della Sesia', 'Dogliani', 'Dolcetto d´Acqui', 'Dolcetto d´Asti',
                'Dolectto d´Alba', 'Dolcetto delle Langhe Monregalesi', 'Dolcetto di Diano d´Alba', 'Dolcetto di Dogliani',
                'Dolcetto di Ovada', 'Erbaluce di Caluso', 'Fara', 'Freisa d´Asti', 'Freisa di Chieri', 'Gabiano', 'Gattinara',
                'Gavi', 'Ghemme', 'Grignolino d´Asti', 'Grignolino del Monferrato Casalese', 'Langhe', 'Lessona', 'Loazzolo',
                'Malvasia di Casorzo d´Asti', 'Malvasia di Castelnuovo Don Bosco', 'Monferrato', 'Moscato d´Asti',
                'Nebbiolo d´Alba', 'Nizza', 'Piemonte', 'Piemonte Moscato Passito', 'Pinerolese', 'Roero', 'Rubino di Cantavenna',
                'Ruche di Castagnole Monferrato', 'Sizzano', 'Terre Alfieri', 'Valli Ossolane', 'Valsusa', 'Verduno Pelaverga'
            ],
            'Puglia' => ['','Aleatico di Puglia', 'Alezio', 'Barletta', 'Brindisi', 'Cacc´e Mitte di Lucera', 'Canosa', 'Castel del Monte','Colline Joniche Tarantine', 'Copertino', 'Daunia', 'Galatina', 'Gioia del Colle', 'Gravina', 'Leverano', 'Lizzano','Locorotondo', 'Martina Franca', 'Matino', 'Moscato di Trani', 'Murgia', 'Nardo', 'Orta Nova', 'Ostuni',
                'Primitivo del Salento', 'Primitivo di Manduria', 'Puglia', 'Rosso di Cerignola', 'Salento', 'Salice Salentino','San Severo', 'Squinzano', 'Tarantino', 'Tavoliere', 'Terra d´Otranto', 'Valle d´Itria'],
            'Sicilia' => ['','Alcamo', 'Avola', 'Camarro', 'Cerasuolo di Vittoria', 'Cerasuolo di Vittoria Classico', 'Contea si Sclafani','Contessa Entellina', 'Delia Nivolelli Nero d´Avola', 'Eloro', 'Erice', 'Etna', 'Etna bianco', 'Etna rosato','Etna rosso', 'Faro', 'Fontanarossa di Cerda', 'Malvasia delle Lipari', 'Mamertino di Milazzo', 'Marsala','Menfi', 'Monreale', 'Moscato di Noto Naturale', 'Moscato di Pantelliera', 'Moscato di Siracusa', 'Noto',
                'Pantelliera', 'Passito di Pantelliera', 'Riesi', 'Salaparuta', 'Salemi', 'Salina', 'Sambuca di Sicilia','Santa Margherita di Belice', 'Sciacca', 'Sicilia', 'Siracusa', 'Terre Siciliane', 'Valle Belice', 'Vittoria'
            ],
            'Toscana' => ['',
                'Alta Valle della Greve', 'Ansonica Costa dell´Argentario', 'Barco Reale di Camignano', 
                'Bianco Pisano di San Trope', 'Bianco dell´Empolese', 'Bianco di Pitigliano', 
                'Bolgheri', 'Bolgheri Sassicaia', 'Brunello di Montalcino', 
                'Candia die Colli Apuani', 'Capalbio', 'Carmignano', 'Chianti', 'Chianti Classico', 
                'Colli dell´Etruria Centrale', 'Colli della Toscana Centrale', 'Colli di Luni', 
                'Colline Lucchesi', 'Cortona', 'Costa Toscana', 'Elba', 'Elba Aleatico Passito', 
                'Grance Senesi', 'Maremma Toscana', 'Montecarlo', 'Montecastelli', 'Montecucco', 
                'Monteregio di Massa Marittima', 'Montescudaio', 'Morellino di Scansano', 
                'Moscedello di Montalcino', 'Orcia', 'Parrina', 'Pomino', 'Rosso Toscano', 
                'Rosso di Montalcino', 'Rosso di Montepulciano', 'San Gimignano', 'Sant´Antimo', 
                'Sovana', 'Suvereto', 'Terratico di Bibbona', 'Terra di Casole', 'Terra di Pisa', 
                'Toscana', 'Val d´Arbia', 'Val di Cornia', 'Val di Magra', 'Val d´Arno di Sopra', 
                'Valdichiana', 'Valdinievole', 'Varnaccia di San Gimignano', 'Vin Santo del Chianti', 
                'Vin Santo del Chianti Classico', 'Vin Santo di Carmignano', 'Vin Santo di Montepulciano', 
                'Vino Nobile di Montepulciano'
            ],
            'Trentino Alto-Adige' =>  ['','Alto Adige / Südtirol','Alto Adige Terlano','Casteller','Delle Venezie','Lago di Caldaro','Mitterberg','Teroldego Rotaliano','Trentino','Trento','Trevenezie','Valdadige','Valdadige Terradeiforti','Vallagarina','Vigneti delle Dolomiti / Weinberg Dolomiten'],
            'Umbria' => [ '','Alto Adige / Südtirol','Alto Adige Terlano','Casteller','Delle Venezie','Lago di Caldaro','Mitterberg','Teroldego Rotaliano','Trentino','Trento','Trevenezie','Valdadige','Valdadige Terradeiforti','Vallagarina','Vigneti delle Dolomiti / Weinberg Dolomiten'],
            'Valle d´Aosta' => ['','Valle d´Aosta'],
            'Veneto' => ['','Alto Livenza', 'Alto Mincio', 'Amarone della Valpolicella Classico', 'Amarone della Valpolicella', 'Arcole', 'Asolo Prosecco', 'Bagnoli Friularo', 'Bagnoli di Spora / Bagnoli', 'Bardolino', 'Bardolino Classico', 'Bianco di Custoza', 'Breganze', 'Colli Berici', 'Colli Euganei', 'Colli Euganei Fior d´Arancio', 'Colli Trevigiani', 'Colli di Conegliano', 'Conegliano Valdobbiadene Prosecco', 'Conselvano', 'Corti Benedettine del Padovano', 'Delle Venezie', 'Gambellara', 'Gambellara Classico', 'Garda', 'Lessini Duello', 'Lison', 'Lison Pramaggiore', 'Lugana', 'Marca Trevigiana', 'Merlara', 'Monovitigno Corvina Veronese', 'Montello', 'Montello E Colli Asolani', 'Monti Lessini', 'Piave', 'Piave Malanotte', 'Prosecco', 'Prosecco Treviso', 'Recioto della Valpolicella', 'Recioto di Soave', 'Recioto di Gamellara', 'Ripasso Superiore Della Valpolicella', 'Riviera del Brenta', 'San Martino della Battaglia', 'Soave', 'Soave Classico', 'Valdadige', 'Valdadige Terra die Forti', 'Valdobbiadene Prosecco', 'Vallagarina', 'Valpolicella', 'Valpolicella Classico', 'Valpolicella Ripasso', 'Valpolicella Ripasso Classico', 'Valpolicella Ripasso Valpantena', 'Veneto', 'Veneto Orientale', 'Venezia', 'Verona', 'Vicenza', 'Veronese', 'Vigneti della Serenissima', 'Vigneti delle Dolomiti'],
            'Andaluca' => ['','Altiplano de Sierra Nevada', 'Bailen', 'Condado de Huelva', 'Cumbres de Guadalfeo', 'Cadiz', 'Cordoba', 'Desierto de Almeria', 'Granada', 'Jerez /Xeres/Sherry', 'Laujar-Alpujarra', 'Lebrija', 'Los Palacios', 'Manzanilla', 'Montilla-Moriles', 'Malaga', 'Norte de Almeria', 'Ribera del Andarax', 'Sierra Norte de Sevilla', 'Sierra Sur de Jaen', 'Sierras de Las Estancias y Los Filabres', 'Sierras de Malaga', 'Torreperogil', 'Villaviciosa de Cordoba'],
            'Aragon' => ['','Bajo Aragon', 'Calatayud', 'Cambo de Borja', 'Carinena', 'Cava', 'Pago Ayles', 'Ribera del gallego-Cinco Villas', 'Ribera del Jiloca', 'Ribera del Queiles', 'Somontano', 'Valdejalon', 'Valle del Cinca'],
            'Asturias' => ['','Cangas'],
            'Baleares' => ['','Binissalem-Mallorca', 'Formentera', 'Ibiza', 'Illes Balears', 'Isla de Menorca', 'Mallorca', 'Pla i Llevant', 'Serra de Tramuntana-Costa Nord'],
            'Canarias' => ['','Abona', 'El Hierro', 'Gran Canaria', 'Islas Canarias', 'La Gomera', 'La Palma', 'Lanzarote', 'Tacoronte-Acentejo', 'Valle de Güimar', 'Valle de la Orotava', 'Ycoden-Daute-Isora'],
            'Cantabria' => ['','Costa de Cantabria','Liebana'],
            'Castilla La Mancha' => ['','Almansa', 'Campo de la Guardia', 'Casa del Blanco', 'Castilla', 'Dehesa del Carrizal', 'Domino de Valdepusa', 'Finca Elez', 'Guijoso', 'Galvez', 'Jumilla', 'La Mancha', 'Machuela', 'Mentrida', 'Mondejar', 'Pago Calzadillla', 'Pago Florentino', 'Pozohondo', 'Ribera del Jucar', 'Sierra de Alcaraz', 'Ucles', 'Valdepenas'],
            'Castilla y Leon' => ['','Arianza', 'Arribes', 'Bierzo', 'Castilla y Leon', 'Cava', 'Cabreros', 'Cigales', 'Ribera del Duero', 'Rueda', 'Sardon de Duero', 'Sierra de Salamanca', 'Tierra de Leon', 'Tierra del vino de Zamora', 'Toro', 'Valles de Benavente', 'Valtiendas'],
            'Cataluna' => ['','Alella', 'Catalunya', 'Cava', 'Conca Del Riu Anoia', 'Conca de Barbera', 'Corpinnat', 'Costers del Segre', 'Emporda', 'Montsant', 'Penedes', 'Pia de Bages', 'Priorat', 'Tarragona', 'Terra Alta'],
            'Extremadura' => ['','Cava','Extremadura','Ribera del Guadiana'],
            'Galicia' => ['','Barbanza e Iria', 'Betanzos', 'Monterrei', 'Ribeira Sacra', 'Ribeiras do Morrazo', 'Ribeiro', 'Rias Baixas', 'Valdeorras', 'Valle del Mino-Orense'],
            'La Rioja' => ['','Cava','Rioja','Valles de Sadacia'],
            'Madrid' => ['','Vinos de Madrid'],
            'Murcia' => ['','Abanilla', 'Bullas', 'Campo de Cartagena', 'Jumilla', 'Yecla'],
            'Navarra' => ['','3 Riberas', 'Baja Montana', 'Cava', 'Pago Finca Bolandin', 'Pago de Arinzano', 'Pago de Otazu', 'Prado de Irache', 'Ribera Alta', 'Ribera Baja', 'Ribera del Quelles', 'Tierra Estella', 'Valdizarbe'],
            'Pais Vasco' => ['','Arabako Txakolina', 'Bizkaiko Txakolina', 'Cava', 'Getariako Txakolina', 'Rioja Alavesa', 'Vizcaya Txakolina'],
            'Valencia' => ['','Arabako Txakolina', 'Bizkaiko Txakolina', 'Cava', 'Getariako Txakolina', 'Rioja Alavesa', 'Vizcaya Txakolina'],
            'Alsace' => ['','Alsace', 'Alsace Edelzwicker', 'Alsace Grand Cru', 'Bas Rhin', 'Cremant dÁlsace', 'Haut Rhin'],
            'Auvergne' => ['','Cantal','Puy de Dome'],
            'Beaujolais' => ['','Beaujolais', 'Beaujolais-Villages', 'Brouilly', 'Chiroubles', 'Chenas', 'Citeaux du Lyonnais', 'Cote de Brouilly', 'Fleurie', 'Julienas', 'Morgon', 'Moulin a Vent', 'Regnie', 'Saint-Amour'],
            'Bordeaux' => ['','Barsac', 'Blaye', 'Blaye-Cotes de Bordeaux', 'Bordeaux Clairet', 'Bordeaux Cotes de France', 'Bordeaux Haut-Benauge', 'Bordeaux Rose', 'Bordeaux Sec', 'Bordeaux Superieur', 'Cadillac', 'Cadillac-Cotes de Bordeaux', 'Canon-Fronsac', 'Castillon-Cotes de Bordeaux', 'Cremant de Bordeaux', 'Cerons', 'Cotes de Blaye', 'Cote de Bordeaux', 'Cote de Bordeaux Saint-Macaire', 'Cotes de Bourg', 'Cotes de Castillon', 'Entre-Deux-Mers', 'Entre-Deux-Mers Haut-Benauge', 'Francs-Cotes de Bordeaux', 'Fronsac', 'Graves', 'Graves Superieures', 'Graves de Vayres', 'Haut-Medoc', 'Lalande de Pomerol', 'Listrac-Medoc', 'Loupiac', 'Lussac Saint-Emilion', 'Moulis en Medoc', 'Medoc', 'Paulliac', 'Pessac-Leognan', 'Pomerol', 'Premieres Cote de Blaye', 'Premieres Cote de Bordeaux', 'Puisseguin Saint Emilion', 'Saint-Emilion', 'Saint-Emilion Grand Cru', 'Saint-Estephe', 'Saint-Georges Saint-Emilion', 'Saint-Julien', 'Sainte-Croix du Mont', 'Sainte-Foy Bordeaux', 'Sauternes'],
            'Burgund' => ['',"Aloxe-Corton","Auxey-Duresses","Auxois","Batrad-Montrachet","Beaune","Bienvenues-Batard-Montrachet","Blagny","Bonnes-Mares","Bourgogne","Bourgogne Aligote","Bourgogne Chitry","Bourgogne","Choulanges-la-Vineuse","Bourgogne Cote Chalonnaise","Bourgogne Cote Saint-Jacques","Bourgogne Cote dÓr","Bourgogne Cote dÓr","Bourgogne Cote du Couchois","Bourgogne Cote d´Auxerre","Bourgogne Epineuil","Bourgogne Haut-Cotes de Beaune","Bourgogne Hautes-Cotes de Nuits","Bourgogne La Chapelle Notre-Dame","Bourgogne Le Chapitre","Bourgogne Montrecul","Bourgogne Mousseux","Bourgogne Passe-tout-grains","Bourgogne Tonnerre","Bouzeron","Chablis","Chablis 1er Cru","Chablis Grand Cru","Chambertin","Chambertin Clos-de-Beze","Chambolle Musigny","Chapelle Chambertin","Charlemagne","Charmes-Chambertin","Chassagne-Montrachet","Chevalier-Montrachet","Chorey-les-Beaune","Clos Saint-Denis","Clos Vougeot","Clos de Tart","Clos de la Roche","Clos des Lambrays","Corton","Corton-Charlemagne","Coteaux Bourguignons","Coteaux de Tannay","Criots Batard-Montrachet","Cremant de Bourgogne","Cote de Beaune","Cotes de Beaune-Villages","Cote de Nuits-Villages","Echezeaux","Fixin","Gevrey-Chambertin","Givry","Grands Echezeaux","Griotte-Chambertin","Irancy","La Grande Rue","La Romanee","La Tache","Ladoix","Latricieres-Chambertin","Maranges","Marsannay","Marsanny Rose","Matis-Chambertin","Mazoyeres-Chambertin","Mercuery","Meursault","Meursault-Blagny","Montagny","Monthelie","Montrachet","Morey Saint-Denis","Musigny","Macon","Macon Superieur","Macon-Aze","Macon-Bray","Macon-Burgy","Macon-Bussieres","Macon-Chaintre","Macon-Chardonnay","Macon-Charnay-les-Macon","Macon-Cruzille","Macon-Davaye","Macon-Fuisse","Macon-Ige","Macon-La Roche-Vineuse","Macon-Loche","Macon-Lugny","Macon-Mancey","Macon-Milly-Lamartine","Macon-Montbellet","Macon-Pierreclos","Macon-Prisse","Macon-Perrone","Macon-Saint-Genoux-le-National","Macon-Serrieres","Macon-Solutre-Pouilly","Macon-Uchizy","Macon-Vergisson","Macon-Verze","Macon-Villages","Macon-Vinzelles","Nievre","Nuits-Saint-Georges","Pernand-Vergelesses","Petit Chablis","Pommard","Pouilly-Fuisse","Pouilly-Loche","Pouilly-Vinzelles","Puligny-Montrachet","Richebourg","Romanee Saint-Vivant","Romanee-Conti","Ruchottes-Chambertin","Rully","Saint-Aubin","Saint-Bris","Saint-Romain","Saint-Veran","Santeany","Savigny-les-Beaune","Saone-et-Loire","Vezelay","Vire-Clesse","Volnay","Vosne-Romanee","Vougeot","Yonne"],
            'Bretagne' =>  ['',"Cidre de Bretagne", "Cidre de Cornouaille", "Cidre de variete Guillevic", "Pommeau de Bretagne"],
            'Champagne' => ['',"Champagne", "Champagne", "Coteaux Champenois", "Coteaux de Coiffy", "Haute-Marne", "Rose des Riceys"],
            'Corse' => ['',"Corse", "Ajaccio", "Corse", "Corse Clavi", "Corse Coteaux du Cap Corse", "Corse Figari", "Corse Porto Vecchio", "Corse Sartene", "Ile de Beaute", "Muscat du Cap Rose", "Patrimonio"],
            'Ile de France' => ['','Ile de France'],
            'Jura' => ['',"Arbois", "Arbois Pupillin", "Chateau-Chalon", "Cremant de Jura", "Cotes de Jura", "Doubs", "Franche Comte", "L´Etoile", "Macvin du Jura"],
            'Languedoc-Roussillion' => ['',"Adrailhou", "Aude", "Banyuls", "Banyuls Grand Cru", "Bessan", "Blanquette de Limoux", "Benovie", "Bernage", "Cabardes", "Cassan", "Catalan", "Caux", "Cessenon", "Cite de Carcassonne", "Clairette du Languedoc", "Collines de la Moure", "Collioure", "Corbieres", "Corbieres Boutenac", "Costieres de Nimes", "Coteaux Flaviens", "Coteaux d´Enserune", "Coteaux de Bessilles", "Coteaux de Beziers", "Coteaux de Ceze", "Coteaux de Fenouilledes", "Coteuax de Foncaude", "Coteaux de Laurens", "Coteuax de Miramont", "Coteaux de Murviel", "Coteuax de Narbonne", "Coteaux de Peyriac", "Coteaux de la Cabrerisse", "Coteaux du Languedoc", "Coteaux du Libron", "Coteaux de Littoral Audois", "Coteaux de Pont du Gard", "Coteaux de Limoux", "Cucugnan", "Cevennes", "Cote Vermeille", "Cotes Catalanes", "Cotes de Lastours", "Cotes de Prouille", "Cotes de Perignan", "Cotes de Thau", "Cotes de Thongue", "Cotes du Brian", "Cotes du Ceressou", "Cotes du Roussillion", "Cotes du Roussillion Villages", "Cotes du Roussillion Villages Caramany", "Cotes du Roussillion Villiages Latour de France", "Cotes du Roussillion Villages Lesquerde", "Cotes du Roussillion Villages Tautavel", "Cote du Vidourel", "Duche d´Uzes", "Faugeres", "Fitou", "Gard", "Gorges de l´Herault", "Haut Vallee de l´Aude", "Haut Vallee de l´Orb", "Hauterive den Pays d´Aude", "Hauts de Badens", "Herault", "La Clape", "Languedoc", "Languedoc Cabrieres", "Languedoc Fonseranes", "Languedoc Gres de Montpellier", "Languedoc La Mejanelle", "Languedoc Montpeyroux", "Languedoc Pezenas", "Languedoc Quatourze", "Languedoc Saint-Christol", "Languedoc Saint-Drezery", "Languedoc Saint-Georges d´Orques", "Languedoc Saint-Saturnin", "Languedoc Sommieres", "Languedoc Terrasses de Beziers", "Limoux", "Malepere", "Maury", "Maury Sec", "Minervois", "Minervois-La-Liviniere", "Mont Baudile", "Monts de la Grage", "Muscat de Frontignan", "Muscat de Lunel", "Muscat de Mireval", "Muscat de Rivesaltes", "Muscat de Saint-Jean de Minervois", "Mediterranee", "Pays Cathare", "Pays d´Oc", "Pic Saint-Loup", "Picpoul de Pinet", "Pyrenees-Orientales", "Rivesaltes", "Rivsesaltes sec", "Sables du Camargue", "Sables du Golfe du Lion", "Saint-Chinian", "Saint-Chinian - Berlou", "Saint-Chinian - Roquebrun", "Saint-Guihem-Le-Desert", "Terrasses du Lazarc", "Terres du Midi", "Torgan", "Val de Cesse", "Val de Dagne", "Val de Montferrand", "Vallee du Paradis", "Vals d´Agly", "Vaunage", "Vicomte d´Aumelas", "Vistrenque"],
            'Lorraine' => ['',"Cotes de Meuse", "Cotes de Toul", "Meuse", "Moselle"],
            'Nord' => [],
            'Normandie' => ['',"Calvados", "Cidre Pays d´Auge", "Cidre de Normandie", "Pays d´Auge Cambremer"],
            'Outre-Mer' => ['',"Guadeloupe", "Guyane", "Martinique", "Mayotte", "Nouvelle-Caledonie", "Polynesie francaise", "Reunion", "Saint-Barthelemy", "Saint-Martin", "Saint-Pierre-et-Miquelon", "Wallis et Futuna"],
            'Cognac' => ['',"Charentais", "Cognac", "Deux-Sevres", "Pineau des Charentes", "Vienne"],
            'Provence' =>  ['',"Aigues", "Alpes Martitimes", "Aples de Haut Provence", "Alpilles", "Argens", "Bandol", "Bellet", "Bouches du Rhone", "Cassis", "Coteaux Varois", "Coteaux d´Aic-En-Provence", "Coteaux-du-Verdon", "Cotes de Provence", "Cotes de Provence Frejus", "Cotes de Provence La-Londe", "Cotes de Provence Pierrefeu", "Cotes de Provence Sainte-Victoire", "Hautes Alpes", "Les Baux de Provence", "Maures", "Mont-Chaumes", "Mediterranee", "Palette", "Petite Crau", "Principaute d´Orange", "Var"],
            'Savoie' => ['',"Ain", "Allobroges", "Bugey", "Bugey-Cerdon", "Chignin", "Chignin-Bergeron", "Coteaux de l´Ain", "Coteaux du Gresivaudan", "Cremant du Savoie", "Crepy", "Isere", "Roussette de Monterminod", "Roussette de Savoie", "Roussette de Bugey", "Savoie", "Seyssel", "Vin de Savoie Ripaille"],
            'Sud-Oest' => ['',"Agennais", "Armagnac", "Atalantique", "Aveyron", "Bas-Armagnac", "Bergerac", "Bigorre", "Brulhois", "Buzet", "Bearn", "Cahors", "Comte-Tolosan", "Correze", "Coteaux de Glanes", "Coteaux de Quercy", "Coteaux et Terrasses de Montauban", "Cotes de Bergerac", "Cotes de Duras", "Cotes de Gascogne", "Cotes de Millau", "Cotes de Montestruc", "Cotes de Montravel", "Cotes du Condomois", "Cotes du Frontonnais", "Cotes du Lot", "Cotes du Marmandais", "Cotes-du-Tarn", "Dordogne", "Entraygues et le Fel", "Estaing", "Floc de Gascogne", "Fronton", "Gaillac", "Gers", "Gironde", "Haut-Montravel", "Haute-Garonne", "Iroulegyu", "Jurancon", "Landes", "Lavilledieu", "Lot", "Lot et Garonne", "Madrian", "Marcillac", "Monbazillac", "Montravel", "Pacherenc du Vic-Blh", "Pays de Brive", "Pecharmant", "Perigord", "Rosette", "Saint-Mont", "Saint-Sardos", "Saussignac", "Tarn-et-Garonne", "Terroirs Landais", "Thezac-Perricard", "Tursan"],
            'Vallee de la Loire' => ['',"Anjou", "Anjou-Coteaux de la Loire", "Anjou-Gamay", "Anjou-Villages", "Anjou-Villages Brissac", "Bonnezeaux", "Bourbonnais", "Bourgueil", "Cabernet d´Anjou", "Chaume", "Cher", "Cheverny", "Chinon", "Chateaumeillant", "Coteaux d´Ancenis", "Coteaux de l´Aubance", "Coteaux du Cher et de l´Arnon", "Coteaux du Giennois", "Coteaux du Loir", "Coteaux du Vendomois", "Coteaux-Charitois", "Coteaux-de-Saumur", "Coulee de Serrant", "Cour-Cheverny", "Cremant de Loir", "Cote Roannaise", "Cotes d´Auvergne", "Cotes du Forez", "Deux-Sevres", "Fiefs Vendeens", "Gros Plant du Pays Nantais", "Haut-Poitou", "Indre et Loire", "Jardin de la France", "Jasnieres", "Loir et Cher", "Loire Atlantique", "Loiret", "Maine-et-Loire", "Marches de Bretagne", "Mentou-Salon", "Montlouis", "Muscadet", "Muscadet Coteaux de la Loire", "Musacadet Cotes de Grand-Lieu", "Msacadet Sevre-et-Maine", "Muscadet-sur-Lie", "Orleans", "Orleans Clery", "Pouilly-Fume", "Pouilly-sur-Loire", "Quarts de Chaume", "Quincy", "Retz", "Reuilly", "Rose d´Anjou", "Saint-Nicolas de Bourgueil", "Saint-Pourcain", "Sancerre", "Sarthe", "Saumur", "Saumur Puy-Notre-Dame", "Saumur-Champigny", "Savennieres", "Savennieres Coulee-de-Serrant", "Savennieres Roche-aux-Moines", "Touraine", "Touraine Amboise", "Touraine Azay le Rideau", "Touraine Chenonceaux", "Touraine Mesland", "Touraine Noble-Joue", "Touraine Oisly", "Val de Loire", "Valencay", "Vendee", "Vienne", "Vin du Thouarsais", "Vouvray"],
            'Vallee du Rhone' =>  ['',"Ardeche", "Balmes Dauphinoises", "Beaumes de Venise", "Brezeme", "Cairanne", "Chateau Grillet", "Chateauneuf-du-Pape", "Chatillon-en-Diois", "Clariette de Bellegarde", "Clairette de Die", "Collines Rhodaniennes", "Comte de Grignan", "Comtes Rhodaniens", "Condrieu", "Cornas", "Costieres de Nimes", "Coteaux de Die", "Coteuax de Pierrevert", "Coteaux de Tricastin", "Coteaux de l´Ardeche", "Coteaux des Baronnies", "Coteaux du Gresivaudan", "Crozes-Hermitage", "Cremant de Die", "Cote Rotie", "Coteaux de Vienne", "Cotes du Rhone", "Cotes du Rhone Villages", "Cotes du Rhone Villages Cairanne", "Cotes du Rhone Villages Chusclan", "Cotes du Rhone Villages Laudun", "Cotes du Rhone Villages Massif d´Uchaux", "Cotes du Rhone Villages Paln de Dieu", "Cotes du Rhone Villages Puymeras", "Cotes du Rhone Villages Roaix", "Cotes du Rhone Villages Rochegude", "Cotes du Rhone Villages Rousset-les-Vignes", "Cotes du Rhone Villages Sablet", "Cotes du Rhone Villages Saint-Gervais", "Cotes du Rhone Villages Saint-Maurice", "Cotes du Rhones Villages Saint-Pantaleon-les-Vignes", "Cotes du Rhone Villages Signargues", "Cotes du Rhone Villages Suze-la-Rousse", "Cotes du Rhone Villages Seguret", "Cotes du Rhone Villages Valreas", "Cotes du Rhone Villages Visan", "Cotes du Vivarais", "Drome", "Duche d´Uzes", "Gard", "Gigondas", "Grigan-Les-Adhemar", "Hermitage", "Lirac", "Luberon", "Muscat de Beaumes de Venise", "Mediterranee", "Pierrevert", "Principaute d´Orange", "Rasteau", "Saint-Joseph", "Saint-Peray", "Tavel", "Urfe", "Vacqueyras", "Valeras", "Vaucluse", "Ventoux", "Vinsobres"],
            'Vosges' => [],
            'Alentejo' => ['','Alentejo','Alentejano'],
            'Algarve' =>  ['',"Algarve", "Lagoa", "Lagos", "Portimao", "Tavira"],
            'Acores' =>  ['',"Acores", "Biscoitos", "Graciosa", "Pico"],
            'Beira Atalntico' => ['',"Bairrada", "Beira Atalntico", "Sico"],
            'Beira Interior' => ['',"Beira Interior", "Beiras", "Terras de Beira"],
            'Douro' => ['',"Douro", "Duriense", "Moscatel do Douro", "Porto"],
            'Dao' => ['',"Dao", "Lafoes"],
            'Lisboa' =>  ['',"Alenquer", "Arruda", "Bucelas", "Carcavelos", "Colares", "Encostas d´Aire", "Lisboa", "Lourinha", "Torres Vedras", "Obidos"],
            'Madeira' => ['',"Madeira", "Madeirense", "Tierras Madeirenses"],
            'Minho' => ['',"Minho", "Vinho Verde"],
            'Setubal' => ['',"Moscatel de Setubal", "Palmela", "Peninsula de Setubal", "Setubal", "Setubal Roxo", "Terras do Sado"],
            'Tavora e Varosa' => ['','Tavora e Varosa'],
            'Tejo' => ['','Tejo'],
            'Tras-os-Montes' => ['',"Transmontano", "Tras-os-Montes"],
            'Eastern Cape' => ['','Eastern Cape','St Francis Bay'],
            'Kwazulu-Natal' => [],
            'Limpopo' => [],
            'Nothern Cape' => ['',"Central Orange River", "Douglas", "Northern Cape", "Rietriver", "Sutherland-Karoo"],
            'Western Cape' => ['',"Aan-de-Doorns", "Agterkliphoogte", "Bamboes Bay", "Banghoek", "Boberg", "Boesmansrivier", "Bonnievale", "Bot River", "Bottelary", "Breede River Valley", "Breedekloof", "Buffeljags", "Calitzdrop", "Cape Agulhas", "Cape Peninsula", "Cape Point", "Cape South Coast", "Cape Town", "Cederberg", "Ceres", "Citrusadl Mountain", "Citrusdal Valley", "Coastal Region", "Constantia", "Darling", "Devon Valley", "Durbanville", "Eilandia", "Elgin", "Elim", "Franschhoek", "Goudini", "Greyton", "Groenekloof", "Hemel-en-Aarde Valley", "Hemel-en-Aarde Ridge", "Herbertsdale", "Hex River Valley", "Hoopsrivier", "Hout Bay", "Jonkerhoek Valley", "Klaasvoogds", "Klein Karoo", "Klein River", "Koekenaap", "Lambert Bay", "Langeberg-Garcia", "Le Chasseur", "Lower Duivenhoks River", "Lutzville Valley", "Malgas", "Malmesbury", "McGregor", "Montagu", "Nuy", "Olifants River", "Outeniqua", "Overberg", "Paarl", "Papegaaiberg", "Philadelphia", "Piekenierskloof", "Piketberg", "Plettenberg Bay", "Polkadraai Hills", "Prince Albert Valley", "Riebeekberg", "Robertson", "Ruiterbosch", "Scherpenheuvel", "Simonsberg - Paarl", "Simonsberg - Stellenbosch", "Slanghoek", "Spruitdrift", "Stellenbosch", "Stilbaai East", "Stormsvlei", "Sunday´s Glen", "Swartberg", "Swartland", "Swellendam", "Theewater", "Tradouw", "Tradouw Highlands", "Tulbagh", "Tygerberg", "Upper Hemel-en-Aarde Valley", "Upper Langkloof", "Vinkrivier", "Voor Paardeberg", "Vredendal", "Walker Bay", "Wellington", "Western Cape", "Worcester"],
            'Buenos Aires' => [],
            'Catamarca' => ['',"Belen", "Fiambala", "Santa Maria", "Tinogasta"],
            'Chubut' => [],
            'Cordoba' => ['',"Caroya", "Traslasierra"],
            'Jujuy' => [],
            'La Pampa' => ['','25 de Mayo'],
            'La Rioja' => ['','Famatina'],
            'Mendoza' =>['',"Agrelo", "Chacayes", "El Cepillo", "Gualtallary", "Junin", "La Consulta", "La Paz", "Lujan de Cuyo", "Lunlunta", "Maipu", "Paraje Altamira", "Rivadavia", "San Carlos", "San Rafael", "Santa Rosa", "Tunuyan", "Tupungato", "Valle de Uco", "Vista Flores"],
            'Neuquen' => ['','San Patricio del Chanar'],
            'Patagonia' => [],
            'Rio Negro' => ['',"Rio Colorado", "Rio Negro"],
            'Salta' => ['',"Cafayate", "Calchaqui"],
            'San Juan' => ['',"Calingasta", "Jachal", "Pedernal", "Tulum", "Ullum", "Zonda", "Iglesias"],
            'Tucuman' => ['','Amaicha','Colalao'],
            'Gansu' => [],
            'Hebei' => [],
            'Heilongjiang' => [],
            'Henan' => [],
            'Jilin' => [],
            'Liaoning' => [],
            'Ningxia' => ['',"Helan", "Hongsipu", "Qingtongxia", "Shizuishan", "Yinchuan", "Yongning"],
            'Shandong' =>  ['',"Penglai", "Qingdao", "Yantai"],
            'Shanxi' => [],
            'Tianjin' => [],
            'Xinjiang' => [],
            'Yunnan' => [],
            'Aconcagua' => ['',"Aconcagua", "Casablanca", "Leyda", "Lo Abarca", "San Antonio"],
            'Atacama' => ['',"Atacama", "Copiapo", "Husaco"],
            'Austral' =>  ['',"Cautin", "Osorno"],
            'Central Valley' => ['',"Apalta", "Cachapoal", "Cauquenes", "Colchagua", "Curico", "Lontue", "Los Lingues", "Maipo", "Maule", "Peumo", "Puente Alto", "Rapel"],
            'Coquimbo' => ['',"Choapa", "Elqui", "Limari"],
            'Southern Chile' => ['',"Bio-Bio", "Itata", "Malleco"],
            'Podravje (Sava Valley)' => ['',"Goricko", "Haloze", "Lendava", "Ljutomer-Ormoz", "Maribor", "Radgonska", "Srednje Slovenske Gorice"],
            'Podravje (Lower Sava Valley)' =>  ['',"Bela krajina", "Bizeljsko-Sremic", "Dolenjska"],
            'Primorska (Littoral)' => ['',"Goriska Brda", "Istra (Koper)", "Kras", "Vipavska Dolina"],
            'Lebanon' => ['','Bekaa Valley'],
            'Agean Islands' => ['',"Chios", "Cyclades", "Ikaria", "Lesvos", "Limnos", "Lipsi", "Mykonos", "Paros", "Rodos", "Samos", "Santorini", "Sikinos", "Syros", "Tinos"],
            'Epirus' => ['',"Ioannina", "Metsovo", "Zitsa"],
            'Ionian Islands' => ['',"Corfu", "Kefalonia", "Lefkada", "Slopes of Aenos", "Zakynthos"],
            'Kreta' => ['',"Corfu", "Kefalonia", "Lefkada", "Slopes of Aenos", "Zakynthos"],
            'Makedonia' => ['',"Amyntaio", "Chalkidiki", "Drama", "Epanomi", "Florina", "Giannitsa", "Grevena", "Imathia", "Kastoria", "Kavala", "Mount Athos", "Naoussa", "Pangeon", "Piera", "Plagies Melitona", "Serres", "Siatista", "Sithonia", "Thasos", "Thessaloniki", "Velvendo Kozanis"],
            'Peloponnes' => ['',"Ahaia", "Aigiala", "Argolida", "Llia", "Klimenti", "Korinthia", "Lakonia", "Letrini Llias", "Mantinia", "Messinia", "Monemvasia", "Namea", "Patra", "Rio Patras", "Tegea", "Trifillia"],
            'Sterea Ellada / Central Greece' => ['',"Afrati Evia", "Agios Konstantinos", "Aliartos", "Anavissos", "Askri", "Asopia Tanagras", "Atlanti", "Attiki", "Dervenohoria", "Distomo", "Dorida", "Evia", "Fokida", "Fthiotida", "Gialtra Edipsos", "Istiaia", "Karistos", "Koropi", "Krania", "Lamia", "Lilantio Pedio Evia", "Malakonta Evia", "Markopoulo", "Martino", "Megara", "Messologi", "Mouriki Thivas", "Oinofita", "Opountia Lokridos", "Orhomenos", "Pallini", "Parnasos Fthiotida", "Parnassos Fokida", "Peania", "Pendeliko", "Pikermi", "Plagies Kitherona Attika", "Plagies Parnithas Viotia", "Plagies Kitherona Viotia", "Plataea", "Prodromos", "Ritsona", "Schimatari", "Spata", "Stylida", "Tanagra", "Thiva", "Vagia Thivas", "Valley of Atalanti"],
            'Thessalia' => ['',"Anhialos", "Elassona", "Karditsa", "Krania", "Krannonas", "Magnisia", "Messenikolas", "Meteora", "Rapsani", "Tyrnavos"],
            'Thraki' => ['','Avdira','Evros','Ismaros'],
            'Black Sea' => ['','Evxinograd','Novi Pazar'],
            'Danubian Plain' => ['',"Lovech", "Lozitsa", "Lyaskovets", "Pavlikeni", "Pleven", "Rouisse", "Svishtov", "Vidin"],
            'Rose Valley' => ['','Sliven'],
            'Struma Valley' => ['','Melnik','Sandanski'],
            'Thracian Valley' => ['',"Assenovgrad", "Brestinik", "Haskovo", "Ivauylovgrad", "Karnobat", "Lyubimets", "Nova Zagora", "Peroushtica", "Plovidiv", "Pomorie", "Sakar", "Septemvri", "Stara Zagora", "Yambol"],
            'Moselle Luxembourgeoise' => ['',"Cremant de Luxembourg", "Luxembourg", "Moselle Luxembourgeoise"],
            'Balaton' => ['',"Badacsony", "Balatonfelvidek", "Balatonfüred-Csopak", "Balatonpglar", "Nagy-Somlo", "Zala"],
            'Duna- The great Hungarian plain (Alföld)' => ['',"Csongrad", "Hajos-Baja", "Kunsag"],
            'Del-Pannonia (South Pannonia)' => ['',"Pecs", "Szekszard", "Tolna", "Villany"],
            'Felso-Magyarorszag (Hegyvidek)' => ['',"Bükk", "Eger", "Matra"],
            'Tokaj' => ['','Tokaj-Hegyalja'],
            'Eszak-Dunantul (North-Transdanubia)' => ['',"Etyek-Buda", "Mor", "Pannonhalma", "Sopron", "Aszar-Neszmely"],
            'Coastal Croatia' =>   ['',"Central and Southern Dalmatia", "Croatian Primorje", "Dalmatian Highlands", "Northern Dalmatia"],
            'Continental Croatia' => ['',"Moslavina", "Plesevica", "Podunavlje", "Pokuplje", "Prigorje-Bilogra", "Slavonia", "Zagorje-Medimurje"],
            'Istria' => [],
            'British Columbia' => ['',"British Columbia", "Cowichan", "Fraser Valley", "Golden Mile Bench", "Gulf Islands", "Naramata Bench", "Okanagan Falls", "Okanagan Valley", "Similkameen Valley", "Skaha Bench", "Vancouver", "Vancouver Island"],
            'Newfoundland' => [],
            'Nova Scotia' => ['',"Annapolis Valley", "Beat River Valley", "LaHave River Valley", "Malagash Peninsula"],
            'Ontario' => ['',"Beamsville Bench", "Creek Shores", "Four Mile Creek", "Lake Erie North Shore", "Lincoln Lakeshore", "Niagara Escarpment", "Niagara Lakeshore", "Niagara Peninsula", "Niagara River", "Niagara-on-the-Lake", "Ontario", "Pelee Island", "Prince Edward County", "Short Hills Bench", "St. David's Bench", "Toronto", "Twenty Mile Bench", "Vinemount Ridge"],
            'Quebec' => ['',"Bas-Saint-Laurent", "Basses Laurentides", "Cantons-de-l'Est", "Centre-du-Quebec", "Lanaudiere", "Laurentides", "Laval", "Monteregie", "Outaouais", "Quebec"],
            'Campbeltown (Scotland)' => [],
            'Cornwall (England)' => [],
            'Devon (England)' => [],
            'Dorset (England' => [],
            'EastAnglia €' => [],
            'Gloucestershire (Eng.)' => [],
            'Hampshire (Eng.)' => [],
            'Herefordshire (Eng.)' => [],
            'Highland ( Scot.)' => [],
            'Island (Scot.)' => [],
            'Islay (Scot.)' => [],
            'Isle of Arran (Eng.)' => [],
            'Isle of Wight (Eng.)' => [],
            'Isle of Scilly (Eng.)' => [],
            'Jersey (Eng.)' => [],
            'Kent (Eng.)' => [],
            'Lincolnshire (Eng.)' => [],
            'London ' => [],
            'Lowland (Scot.)' => [],
            'Northhamptonshire (Eng.)' => [],
            'Oxfordshire (Eng.)' => [],
            'Shropshire (Eng.)' => [],
            'Somerset (Eng.)' => [],
            'Speyside ( Scot.)' => [],
            'Surrey (Eng.)' => [],
            'Sussex (Eng.)' => [],
            'Wales (Wales)' => [],
            'Worcestershire (Eng.)' => [],
            'Yorkshire (Eng.)' => [],
            'Black Sea Coastal Zone' => [],
            'Imereti' => ['','Sviri'],
            'Kakheti' =>  ['',"Akhasheni", "Gurjaani", "Kakheti", "Kardanakhi", "Kindzmarauli", "Kotekhi", "Kvareli", "Manavi", "Mukzani", "Napareuli", "Teliani", "Tibaani", "Tsinandali", "Vazisubani"],
            'Kartli' => ['','Atenuri'],
            'Meskheti' => [],
            'Racha-Lechkhumi / Kvemo Svaneti' => ['','Khvanchkara','Tvishi'],
        ];
        asort($appellationbyregion);

        if(isset($appellationbyregion[$regions])) {
            $appellations = $appellationbyregion[$regions];

            $appellationChoices = array_combine($appellations,$appellations);

            return $appellationChoices;
        } else {
            return [];
        }
    }
    public function getCountry()
    {
        $allcountry = [
            'New Zealand' => 'New Zealand',
            'Austria' => 'Austria',
            'Germany' => 'Germany',
            'Switzerland' => 'Switzerland',
            'South Africa' => 'South Africa',
            'China' => 'China',
            'Italy' => 'Italy',
            'France' => 'France',
            'Australia' => 'Australia',
            'Argentina' => 'Argentina',
            'Luxembourg' => 'Luxembourg',
            'Bulgaria' => 'Bulgaria',
            'Greece' => 'Greece',
            'Romania' => 'Romania',
            'USA' => 'USA',
            'Spain' => 'Spain',
            'Portugal' => 'Portugal',
            'Chile' => 'Chile',
            'Slovenia' => 'Slovenia',
            'Lebanon' => 'Lebanon',
            'Croatia' => 'Croatia',
            'Hungary' => 'Hungary',
            'England' => 'England',
            'Georgia' => 'Georgia',
            'Canada' => 'Canada',
        ];
        asort($allcountry);
        return $allcountry;
    }

    public function getAllProductTypes()
    {
        $allProductTypes = [
            'physical' => 'physical',
            //'digital'  => 'digital',
        ];

        return $allProductTypes;
    }
    public function create_product_vellum($companyApplication, $params = [], $product_data, $seller = null, $extraParams = [])
    {   
        $is_success = FALSE;
        $application = $companyApplication->getApplication();

        $platformHelper = $this->getAppHelper('platform');
      
        $productResponse = $platformHelper->create_platform_product($params);
        
        $productResponse = json_decode($productResponse);
        
        if(isset($productResponse->product->additionalInfoSections)) {
            $additionalinfoArray = [];

            for($i=0;$i<sizeof($productResponse->product->additionalInfoSections);$i++) {
                $title = $productResponse->product->additionalInfoSections[$i]->title;
                $description = $productResponse->product->additionalInfoSections[$i]->description;
                $additionalinfoArray[] = [
                    'title' => $title,
                    'description' => $description,
                ];
            }
           
        }
        $product = new Products;

        if (isset($productResponse->product) && isset($productResponse->product->id)) {

            if(isset($productResponse->product->manageVariants) && !($productResponse->product->manageVariants) && isset($product_data['trackInventory'])) {
                $inventoryItem["inventoryItem"]["trackQuantity"] = $product_data['trackInventory'];
                $inventoryItem["inventoryItem"]["variants"][0]["variantId"] = $productResponse->product->variants[0]->id;
                if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                    $inventoryItem["inventoryItem"]["variants"][0]["quantity"] = $product_data['quantity'];
                }
                if (isset($product_data['inventory_status'] )) {
                    $inventoryItem["inventoryItem"]["variants"][0]["inStock"] = $product_data['inventory_status'];
                }         
                $platformHelper->updateInventory($productResponse->product->id,$inventoryItem);
            }

            $this->assignCategoriesToProducts(
                $product,
                [$productResponse->product->id],
                isset($extraParams['categories']) ? $extraParams['categories'] : []
            );

            $productImage = isset($productResponse->product->media->mainMedia->thumbnail->url) ? $productResponse->product->media->mainMedia->thumbnail->url : "";
            
            $product->setName($productResponse->product->name);
            $product->setSku($productResponse->product->sku);
            $product->setPrice($productResponse->product->price->price);
            $product->setStockLevel($productResponse->product->stock->inStock);
            $product->setCompany($companyApplication->getCompany());
            $product->setProdId($productResponse->product->id);
            //$product->setStatus($productResponse->product->visible);
            $product->setTimeStamp(time());
            $product->setStoreUrl($productResponse->product->productPageUrl->base.$productResponse->product->productPageUrl->path);
            $product->setCategoryData(isset($extraParams['categories']) ? $extraParams['categories'] : []);
            $product->setWeight($productResponse->product->weight);
            $product->setDescription($productResponse->product->description);
            $product->setBrand(isset($productResponse->product->brand) ? $productResponse->product->brand : "");
            $product->setExtraDetails(serialize($additionalinfoArray));
            //$product->setOriginalName(isset($extraParams['originalName']) ? $extraParams['originalName'] : $productResponse->product->name);
            $product->setImage($productImage);

            if (isset($extraParams['commission']) && isset($extraParams['commission_type'])) {
                
                $product->setCommission($extraParams['commission']);
                $product->setCommissionType($extraParams['commission_type']);
            }
        
            if (isset($product_data['trackInventory'])) {
                $product->setTrackInventory($product_data['trackInventory']);
                if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                    $product->setQuantity($product_data['quantity']);
                }
            }
            if (isset($product_data['inventory_status'] )) {
                $product->setInStock($product_data['inventory_status']);
            }

            if (!empty($seller) && isset($params['product']['visible'])) {
                
                $product->setSeller($seller);
                ($params['product']['visible'] == false) ? $product->setStatus('N') : $product->setStatus('A');
                
            } else {
                $product->setStatus('A');
            }

            if (!empty($seller) && isset($extraParams['fromCsv']) && $extraParams['fromCsv'] == true) {
                //($params['product']['visible'] == false) ? $product->setStatus('N') : $product->setStatus('A');
                $product->setStatus(
                    isset($extraParams['status_to']) ? $extraParams['status_to'] : "A"
                );
            } elseif (empty($seller) && isset($extraParams['fromCsv']) && $extraParams['fromCsv'] == true) {
                ($params['product']['visible'] == false) ? $product->setStatus('D') : $product->setStatus('A');
            }
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $is_success = TRUE;
        } else {
            $is_success = FALSE;
        }
        
        return [$is_success, $productResponse, $product];
    }    
    public function create_product($companyApplication, $params = [], $product_data, $seller = null, $extraParams = [])
    {   
        $is_success = FALSE;
        $application = $companyApplication->getApplication();

        $platformHelper = $this->getAppHelper('platform');
        

        $productResponse =  $platformHelper->create_platform_product($params);
       
        $productResponse = json_decode($productResponse);
   
        $product = new Products;

        if (isset($productResponse->product) && isset($productResponse->product->id)) {
            if(isset($productResponse->product->manageVariants) && !($productResponse->product->manageVariants) && isset($product_data['trackInventory'])) {
                $inventoryItem["inventoryItem"]["trackQuantity"] = $product_data['trackInventory'];
                $inventoryItem["inventoryItem"]["variants"][0]["variantId"] = $productResponse->product->variants[0]->id;
                if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                    $inventoryItem["inventoryItem"]["variants"][0]["quantity"] = $product_data['quantity'];
                }
                if (isset($product_data['inventory_status'] )) {
                    $inventoryItem["inventoryItem"]["variants"][0]["inStock"] = $product_data['inventory_status'];
                }         
                $platformHelper->updateInventory($productResponse->product->id,$inventoryItem);
            }
            $this->assignCategoriesToProducts(
                $product,
                [$productResponse->product->id],
                isset($extraParams['categories']) ? $extraParams['categories'] : []
            );

            $productImage = isset($productResponse->product->media->mainMedia->thumbnail->url) ? $productResponse->product->media->mainMedia->thumbnail->url : "";
            $price_data = array(
                'discount_type' => $productResponse->product->discount->type,
                'discount' =>  $productResponse->product->discount->value,
                'sale_price' => $productResponse->product->priceData->discountedPrice,  
            );
            $price_data = serialize($price_data);
            $product->setName($productResponse->product->name);
            $product->setSku($productResponse->product->sku);
            $product->setPrice($productResponse->product->price->price);
            $product->setPriceData($price_data);
            $product->setStockLevel($productResponse->product->stock->inStock);
            $product->setCompany($companyApplication->getCompany());
            $product->setProdId($productResponse->product->id);
            //$product->setStatus($productResponse->product->visible);
            $product->setTimeStamp(time());
            $product->setStoreUrl($productResponse->product->productPageUrl->base.$productResponse->product->productPageUrl->path);
            $product->setCategoryData(isset($extraParams['categories']) ? $extraParams['categories'] : []);
            $product->setWeight($productResponse->product->weight);
            $product->setDescription($productResponse->product->description);
            $product->setBrand(isset($productResponse->product->brand) ? $productResponse->product->brand : "");
        
            //$product->setOriginalName(isset($extraParams['originalName']) ? $extraParams['originalName'] : $productResponse->product->name);
            $product->setImage($productImage);
            
            if (isset($extraParams['commission']) && isset($extraParams['commission_type'])) {
                
                $product->setCommission($extraParams['commission']);
                $product->setCommissionType($extraParams['commission_type']);
            }
            if (isset($product_data['trackInventory'])) {
                $product->setTrackInventory($product_data['trackInventory']);
                if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                    $product->setQuantity($product_data['quantity']);
                }
            }
            if (isset($product_data['inventory_status'] )) {
                $product->setInStock($product_data['inventory_status']);
            }
            if (!empty($seller) && isset($params['product']['visible'])) {
                
                $product->setSeller($seller);
                ($params['product']['visible'] == false) ? $product->setStatus('N') : $product->setStatus('A');
                
            } else {
                $product->setStatus('A');
            }

            if (!empty($seller) && isset($extraParams['fromCsv']) && $extraParams['fromCsv'] == true) {
                //($params['product']['visible'] == false) ? $product->setStatus('N') : $product->setStatus('A');
                $product->setStatus(
                    isset($extraParams['status_to']) ? $extraParams['status_to'] : "A"
                );
            } elseif (empty($seller) && isset($extraParams['fromCsv']) && $extraParams['fromCsv'] == true) {
                ($params['product']['visible'] == false) ? $product->setStatus('D') : $product->setStatus('A');
            }
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $is_success = TRUE;
        } else {
            $is_success = FALSE;
        }
     
        return [$is_success, $productResponse, $product];
    }

    public function get_products($params = [])
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);
        $company = isset($params['company']) ? $params['company'] : $this->container->get('app.runtime')->get_company_application()->getCompany();
        $seller = isset($params['seller']) ? $params['seller'] : null;

        // $seller = isset($params['seller']) ? $params['seller'] : $this->container->get('app.runtime')->sellerCompany;
        $storerepo = $this->entityManager->getRepository(Products::class);
        $products = $storerepo->getProducts($params, $company, $seller);
        return [$products, $params];
    }

    public function get_product($platform_product_id, $params = array(), $platformHelper = null)
    {
        $notifications = [];
        $product_data = [];
        
        if (!$platformHelper) {
            $platformHelper = $this->getAppHelper('platform');
        }
        sleep(2);
        list($response, $error) = $platformHelper->get_platform_product($platform_product_id, $params);
        $response = json_decode($response);

        if (isset($response->product) && !empty($response->product)) {
            $product_data = $response->product;
        } else {
            if (!empty($error)) {
                $notifications['danger'][] = $error;
            } else if (isset($response->message) && !empty($response->message)) {
                $notifications['danger'][] = $response->message;
            }
        }

        return [$product_data, $notifications];
    }

    public function update_product($product_data, $platform_product_id, $product, $seller = null, $extraParams = [])
    {  
        $is_success = false;
        $notifications = [];

        $platformHelper = $this->getAppHelper('platform');

        $platform_product_data = [];

        if (isset($product_data['name']) && !empty($product_data['name'])) {
            $platform_product_data['product']['name'] = $product_data['name'];
        }

        if (isset($product_data['visible'])) {
            $platform_product_data['product']['visible'] = $product_data['visible'];
        }

        if (isset($product_data['productType']) && !empty($product_data['productType'])) {
            $platform_product_data['product']['productType'] = $product_data['productType'];
        }

        if (isset($product_data['description']) && !empty($product_data['description'])) {
            $platform_product_data['product']['description'] = $product_data['description'];
        }

        if (isset($product_data['sku']) && !empty($product_data['sku'])) {
            $platform_product_data['product']['sku'] = $product_data['sku'];
        }

        if (isset($product_data['weight']) && !empty($product_data['weight'])) {
            $platform_product_data['product']['weight'] = $product_data['weight'];
        }

        if (isset($product_data['price']) && !empty($product_data['price'])) {
            $platform_product_data['product']['priceData']['price'] = $product_data['price'];
        }

        if (isset($product_data['brand']) && !empty($product_data['brand'])) {
            $platform_product_data['product']['brand'] = $product_data['brand'];
        }

        if (isset($product_data['discount']) && !empty($product_data['discount'])) {
            $platform_product_data['product']['discount'] = $product_data['discount'];
        }

        if (isset($product_data['images']) && !empty($product_data['images'])) {
            foreach($product_data['images'] as $image) {
                if (is_array($image)) {
                    $mediaData['media'] = [
                        [
                            "url" => isset($image['image_url']) ? $image['image_url'] : "",
                            // "url" => "https://static.wixstatic.com/media/29454d_310104924f2845a891e56254ab7c9753~mv2.png/v1/fit/w_512,h_512,q_90/file.png"
                        ]
                    ]; 
                    list($imgResponse, $error) = $platformHelper->add_product_media($platform_product_id, $mediaData);
                    $imgResponse = json_decode($imgResponse); 
                    sleep(4);
                    if (isset($imgResponse->message) && !empty($imgResponse->message)) {
                        $notifications['danger'][] = $imgResponse->message;
                    } else {
                        $notifications['success'][] = "Image Updated Successfully !!";
                    }
                }
            }
        }
        
        list($response, $error) = $platformHelper->update_platform_product($platform_product_id, $platform_product_data);
        $productResponse = json_decode($response);
       
        if (isset($productResponse->product) && isset($productResponse->product->id)) {
            $platformInventoryUpdated = false;
            if(isset($productData) && isset($productResponse->product->manageVariants) && !($productResponse->product->manageVariants)) {
                $inventoryItem["inventoryItem"]["trackQuantity"] = $product_data['trackInventory'];
                $inventoryItem["inventoryItem"]["variants"][0]["variantId"] = $productResponse->product->variants[0]->id;
                if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                    $inventoryItem["inventoryItem"]["variants"][0]["quantity"] = $product_data['quantity'];
                }
                if (isset($product_data['inventory_status'] )) {
                    $inventoryItem["inventoryItem"]["variants"][0]["inStock"] = $product_data['inventory_status'];
                }         
                $platformHelper->updateInventory($productResponse->product->id,$inventoryItem);
                $platformInventoryUpdated = true;
            }
            if (isset($product_data['categories'])) {
                $this->assignCategoriesToProducts(
                    $product,
                    [$productResponse->product->id],
                    isset($product_data['categories']) ? $product_data['categories'] : []
                );
            }
            
            $productImage = isset($productResponse->product->media->mainMedia->thumbnail->url) ? $productResponse->product->media->mainMedia->thumbnail->url : "";
            //if ($product->getName() == "CTTP63 new") { dd($platform_product_data['product']['name'], $product->getName()); }
            $isReview = false;
            if (!empty($seller)) {
                
                if (
                    isset($platform_product_data['product']['name']) && $platform_product_data['product']['name'] != $product->getName() ||
                    isset($platform_product_data['product']['sku']) && $platform_product_data['product']['sku'] != $product->getSku() ||
                    isset($platform_product_data['product']['weight']) && $platform_product_data['product']['weight'] != $product->getWeight() ||
                    isset($platform_product_data['product']['description']) && $platform_product_data['product']['description'] != $product->getDescription() ||
                    isset($platform_product_data['product']['brand']) && $platform_product_data['product']['brand'] != $product->getBrand() ||
                    isset($platform_product_data['product']['priceData']['price']) && (float) $platform_product_data['product']['priceData']['price'] != (float) $product->getPrice()
                ) {
                    $isReview = true;
                }
                
                if ($product->getStatus() == "N") {
                    $isReview = true;
                }

                $oldCategoriesData = $product->getCategoryData();
                $newCategoriesData = isset($product_data['categories']) ? $product_data['categories'] : [];
                $diffCategoriesData = array_diff($oldCategoriesData, $newCategoriesData);
                
                if (!empty($diffCategoriesData)) {
                    $isReview = true;
                }
                
                if (count($oldCategoriesData) != count($newCategoriesData)) {
                    $isReview = true;
                }
                
            }

            if (!empty($seller)) {

                if ($isReview) {
                    (isset($productResponse->product->visible) && $productResponse->product->visible == true) ? $product->setStatus('A') : $product->setStatus('N');
                }

            } else {
                if (isset($productResponse->product->visible) && $productResponse->product->visible == true) {
                    $product->setStatus("A");
                } elseif ($product->getStatus() == "N" ) {
                    $product->setStatus("N");
                } else {
                    $product->setStatus("D");
                }
            }

            # Changes For Product Batch Action
            if (isset($extraParams['fromBatchAction']) && $extraParams['fromBatchAction'] && empty($seller)) {
                if (isset($extraParams['batchAction']) && strtolower($extraParams['batchAction']) == "disable") {
                    $product->setStatus("D");
                }
            }
            $price_data = array(
                'discount_type' => $productResponse->product->discount->type,
                'discount' =>  $productResponse->product->discount->value,
                'sale_price' => $productResponse->product->priceData->discountedPrice,  
            );
            $price_data = serialize($price_data);
            $product->setName($productResponse->product->name);
            isset($productResponse->product->sku) ? $product->setSku($productResponse->product->sku) : "";
            $product->setPrice($productResponse->product->price->price);
            $product->setPriceData($price_data);
            $product->setStockLevel($productResponse->product->stock->inStock);
            $product->setProdId($productResponse->product->id);
            $product->setImage($productImage);
            //$product->setCategoryData(isset($product_data['categories']) ? $product_data['categories'] : []);
            isset($productResponse->product->weight) ? $product->setWeight($productResponse->product->weight) : "";
            $product->setDescription($productResponse->product->description);
            $product->setBrand(isset($productResponse->product->brand) ? $productResponse->product->brand : "");

            if (isset($product_data['originalName']) && !empty($product_data['originalName'])) {
                //$product->setOriginalName($product_data['originalName']);
            }

            if (isset($product_data['categories'])) {
                $product->setCategoryData($product_data['categories']);
            }

            if (isset($product_data['commission_type'])) {
                $product->setCommissionType($product_data['commission_type']);
            }
            
            if (isset($product_data['commission'])) {
                $product->setCommission($product_data['commission']);
            }
            if($platformInventoryUpdated) {
                if (isset($product_data['trackInventory'])) {
                    $product->setTrackInventory($product_data['trackInventory']);
                    if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                        $product->setQuantity($product_data['quantity']);
                    }
                }
                if (isset($product_data['inventory_status'] )) {
                    $product->setInStock($product_data['inventory_status']);
                }
            }
            
            //$product->setStatus($productResponse->product->visible);

            // if ($seller && $isReview) {
            //     (isset($productResponse->product->visible) && $productResponse->product->visible == true) ? $product->setStatus('A') : $product->setStatus('N');
            // } elseif(!$seller && isset($productResponse->product->visible)) {
            //     ($productResponse->product->visible == true) ? $product->setStatus('A') : $product->setStatus('D');
            // } else {
            //     $product->setStatus('D');
            // }
            
            $product->setTimeStamp(time());
            $product->setStoreUrl($productResponse->product->productPageUrl->base.$productResponse->product->productPageUrl->path);
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $is_success = true;
            
            $this->clear_all_product_caches($productResponse->product->id);

        } else {
            
            if (!empty($error)) {
                $notifications['danger'][] = $error;
            } else if (isset($response->message) && !empty($response->message)) {
                $notifications['danger'][] = $response->message;
            }
            $is_success = false;
        }
        //$this->clear_all_product_caches($productResponse->product->id);
        return [$product, $notifications, $is_success, $productResponse];
    }
    public function update_product_vellum($product_data, $platform_product_id, $product, $seller = null, $extraParams = [])
    {
       ;
        $is_success = false;
        $notifications = [];

        $platformHelper = $this->getAppHelper('platform');

        $platform_product_data = [];

        if (isset($product_data['name']) && !empty($product_data['name'])) {
            $platform_product_data['product']['name'] = $product_data['name'];
        }

        if (isset($product_data['visible'])) {
            $platform_product_data['product']['visible'] = $product_data['visible'];
        }

        if (isset($product_data['productType']) && !empty($product_data['productType'])) {
            $platform_product_data['product']['productType'] = 'physical';
        }
        $desc = [
            [
                '<b>How was the wine stored ?</b></br>',
                $product_data['condition1'] == "Other" ? $product_data['conditionAdd'] : $product_data['condition1'],
            ],
            [
                '</br><b>Cap and Label condition ?</b></br>',
                 $product_data['condition3'],
            ],
            [
                '</br><b>Filling level of the bottle ?</b></br>',
                $product_data['condition2'],
            ],
            [
                '</br><b>Source of supply ?</b></br>',
                $product_data['condition4'] == "Other" ? $product_data['conditionFourthAdd']:
                $product_data['condition4'],
            ],
            [
                '</br>',
                $product_data['extradetails'],
            ]
        ];
        $result = "";
        $counter = 0;
        foreach($desc as $item){
            if(empty($item[1])) {
                continue;
            }else{
                if($counter <= 3) {
                    $result .= "{$item[0]}\n";
                    $result .= "{$item[1]}\n\n";
                } else {
                    $result .= "{$item[1]}\n\n";
                }
            }
            $counter++;
        }    
        // if (isset($product_data['description']) && !empty($product_data['description'])) {
            $platform_product_data['product']['description'] = $result;
        // }
        
        if (isset($product_data['sku']) && !empty($product_data['sku'])) {
            $platform_product_data['product']['sku'] = $product_data['sku'];
        }

        if (isset($product_data['weight']) && !empty($product_data['weight'])) {
            $platform_product_data['product']['weight'] = $product_data['weight'];
        }

        if (isset($product_data['price']) && !empty($product_data['price'])) {
            $platform_product_data['product']['priceData']['price'] = $product_data['price'];
        }

        if (isset($product_data['brand']) && !empty($product_data['brand'])) {
            $platform_product_data['product']['brand'] = $product_data['brand'];
        }

        if (isset($product_data['discount']) && !empty($product_data['discount'])) {
            $platform_product_data['product']['discount'] = $product_data['discount'];
        }

        foreach ($product_data['awards'] as $key => $award_data) {
            if ($award_data == "" ||  $award_data == "Select Awards") {
                continue;
            }
            $award[$key] = $award_data . " - " . $product_data['awardsValue'][$key];
            
        }
        $product_data['awards'] = isset($award) ? $award : [];

        $award_arr = [];
        $award_arr = array_merge($award_arr,$product_data['awards']);
        $result_awards = "";
        foreach($award_arr as $awards) {
            $result_awards .= "{$awards}\n\n,"; 
        }
       
        if (isset($product_data) && !empty($product_data)) {

            $platform_product_data['product']['additionalInfoSections'] = [];
            if (isset($product_data['grape_varity']) && !empty($product_data['grape_varity'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Grape Variety',
                    'description' => $product_data['grape_varity'],
                ];
            }

            if (!empty($product_data['vintage'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Vintage',
                    'description' => $product_data['vintage'],
                ];
            }

            if (!empty($result_awards)) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Awards',
                    'description' => $result_awards,
                ];
            }

            if (!empty($product_data['BottleSize'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'BottleSize',
                    'description' => $this->bottleSize($product_data['BottleSize'])
                ];
            }

            if (!empty($product_data['country'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Country',
                    'description' => $product_data['country'],
                ];
            }

            if (!empty($product_data['region'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Region',
                    'description' => $product_data['region'],
                ];
            }

            if (!empty($product_data['appellation'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Appellation',
                    'description' => $product_data['appellation'],
                ];
            }

            if (!empty($product_data['classification'])) {
                $platform_product_data['product']['additionalInfoSections'][] = [
                    'title' => 'Classification',
                    'description' => $product_data['classification'],
                ];
            }
        }
        
        if (isset($product_data['images']) && !empty($product_data['images'])) {
            foreach($product_data['images'] as $image) {
                if (is_array($image)) {
                    $mediaData['media'] = [
                        [
                            "url" => isset($image['image_url']) ? $image['image_url'] : "",
                            // "url" => "https://cdn11.bigcommerce.com/s-ajo5dorpkd/products/410/images/513/New-fashion-cute-turtle-men-and-women-3D-printing-casual-short-sleeved-T-shirt__25734.1639398108.220.290.jpg?c=1"
                        ]
                    ]; 
                    list($imgResponse, $error) = $platformHelper->add_product_media($platform_product_id, $mediaData);
                    $imgResponse = json_decode($imgResponse);
                    sleep(4);
                    if (isset($imgResponse->message) && !empty($imgResponse->message)) {
                        $notifications['danger'][] = $imgResponse->message;
                    } else {
                        $notifications['success'][] = "Image Updated Successfully !!";
                    }
                }
            }
        }
     
        list($response, $error) = $platformHelper->update_platform_product($platform_product_id, $platform_product_data);
        $productResponse = json_decode($response);
        if(isset($productResponse->product->additionalInfoSections)) {
            $additionalinfoArray = [];

            for($i=0;$i<sizeof($productResponse->product->additionalInfoSections);$i++) {
                $title = $productResponse->product->additionalInfoSections[$i]->title;
                $description = $productResponse->product->additionalInfoSections[$i]->description;
                $additionalinfoArray[] = [
                    'title' => $title,
                    'description' => $description,
                ];
            }
           
        }

        if (isset($productResponse->product) && isset($productResponse->product->id)) {
            
            $platformInventoryUpdated = false;
            if(isset($productResponse->product->manageVariants) && !($productResponse->product->manageVariants)) {
                $inventoryItem["inventoryItem"]["trackQuantity"] = $product_data['trackInventory'];
                $inventoryItem["inventoryItem"]["variants"][0]["variantId"] = $productResponse->product->variants[0]->id;
                if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                    $inventoryItem["inventoryItem"]["variants"][0]["quantity"] = $product_data['quantity'];
                }
                if (isset($product_data['inventory_status'] )) {
                    $inventoryItem["inventoryItem"]["variants"][0]["inStock"] = $product_data['inventory_status'];
                }         
                $platformHelper->updateInventory($productResponse->product->id,$inventoryItem);
                $platformInventoryUpdated = true;
            }

            if (isset($product_data['categories'])) {
                $this->assignCategoriesToProducts(
                    $product,
                    [$productResponse->product->id],
                    isset($product_data['categories']) ? $product_data['categories'] : []
                );
            }
            
            $productImage = isset($productResponse->product->media->mainMedia->thumbnail->url) ? $productResponse->product->media->mainMedia->thumbnail->url : "";
            //if ($product->getName() == "CTTP63 new") { dd($platform_product_data['product']['name'], $product->getName()); }
            $isReview = false;
            if (!empty($seller)) {
                
                if (
                    isset($platform_product_data['product']['name']) && $platform_product_data['product']['name'] != $product->getName() ||
                    isset($platform_product_data['product']['sku']) && $platform_product_data['product']['sku'] != $product->getSku() ||
                    isset($platform_product_data['product']['weight']) && $platform_product_data['product']['weight'] != $product->getWeight() ||
                    isset($platform_product_data['product']['description']) && $platform_product_data['product']['description'] != $product->getDescription() ||
                    isset($platform_product_data['product']['brand']) && $platform_product_data['product']['brand'] != $product->getBrand() ||
                    isset($platform_product_data['product']['priceData']['price']) && (float) $platform_product_data['product']['priceData']['price'] != (float) $product->getPrice()
                ) {
                    $isReview = true;
                }
                
                if ($product->getStatus() == "N") {
                    $isReview = true;
                }

                $oldCategoriesData = $product->getCategoryData();
                $newCategoriesData = isset($product_data['categories']) ? $product_data['categories'] : [];
                $diffCategoriesData = array_diff($oldCategoriesData, $newCategoriesData);
                
                if (!empty($diffCategoriesData)) {
                    $isReview = true;
                }
                
                if (count($oldCategoriesData) != count($newCategoriesData)) {
                    $isReview = true;
                }
                
            }

            if (!empty($seller)) {

                if ($isReview) {
                    (isset($productResponse->product->visible) && $productResponse->product->visible == true) ? $product->setStatus('A') : $product->setStatus('N');
                }

            } else {
                if (isset($productResponse->product->visible) && $productResponse->product->visible == true) {
                    $product->setStatus("A");
                } elseif ($product->getStatus() == "N" ) {
                    $product->setStatus("N");
                } else {
                    $product->setStatus("D");
                }
            }

            # Changes For Product Batch Action
            if (isset($extraParams['fromBatchAction']) && $extraParams['fromBatchAction'] && empty($seller)) {
                if (isset($extraParams['batchAction']) && strtolower($extraParams['batchAction']) == "disable") {
                    $product->setStatus("D");
                }
            }
            
            $product->setName($productResponse->product->name);
            $product->setSku($productResponse->product->sku);
            $product->setPrice($productResponse->product->price->price);
            $product->setStockLevel($productResponse->product->stock->inStock);
            $product->setProdId($productResponse->product->id);
            $product->setImage($productImage);
            //$product->setCategoryData(isset($product_data['categories']) ? $product_data['categories'] : []);
            $product->setWeight($productResponse->product->weight);
            $product->setDescription($productResponse->product->description);
            $product->setBrand(isset($productResponse->product->brand) ? $productResponse->product->brand : "");
            $product->setExtraDetails(serialize($additionalinfoArray));
            if (isset($product_data['originalName']) && !empty($product_data['originalName'])) {
                //$product->setOriginalName($product_data['originalName']);
            }

            if (isset($product_data['categories'])) {
                $product->setCategoryData($product_data['categories']);
            }

            if (isset($product_data['commission_type'])) {
                $product->setCommissionType($product_data['commission_type']);
            }
            
            if (isset($product_data['commission'])) {
                $product->setCommission($product_data['commission']);
            }

            if($platformInventoryUpdated) {
                if (isset($product_data['trackInventory'])) {
                    $product->setTrackInventory($product_data['trackInventory']);
                    if($product_data['trackInventory'] && isset($product_data['quantity'])) {
                        $product->setQuantity($product_data['quantity']);
                    }
                }
                if (isset($product_data['inventory_status'] )) {
                    $product->setInStock($product_data['inventory_status']);
                }
            }
            
            //$product->setStatus($productResponse->product->visible);

            // if ($seller && $isReview) {
            //     (isset($productResponse->product->visible) && $productResponse->product->visible == true) ? $product->setStatus('A') : $product->setStatus('N');
            // } elseif(!$seller && isset($productResponse->product->visible)) {
            //     ($productResponse->product->visible == true) ? $product->setStatus('A') : $product->setStatus('D');
            // } else {
            //     $product->setStatus('D');
            // }
            
            $product->setTimeStamp(time());
            $product->setStoreUrl($productResponse->product->productPageUrl->base.$productResponse->product->productPageUrl->path);
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $is_success = true;
            
            $this->clear_all_product_caches($productResponse->product->id);

        } else {
            
            if (!empty($error)) {
                $notifications['danger'][] = $error;
            } else if (isset($response->message) && !empty($response->message)) {
                $notifications['danger'][] = $response->message;
            }
            $is_success = false;
        }
        //$this->clear_all_product_caches($productResponse->product->id);
        return [$product, $notifications, $is_success, $productResponse];
    }
    public function performBatchAction($request, $formData, $companyApplication, $seller = null)
    {
        $notifications = [];
        $action = $formData['batch_action'];
        $productIds = $request->request->get('product_ids');
        $company = $companyApplication->getCompany();
        if (empty($productIds)) {
            $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.empty_data_for_bulk_action')];

            return $notifications;
        }
        
        $extraParams['batchAction'] = $action;

        switch ($action) {
            case 'disable':
            case 'active':
            case 'block':
                $data = [];
                
                if ($action == 'disable') {
                    $data['visible'] = false;
                } elseif ($action == 'active') {
                    $data['visible'] = true;
                } else {
                    $data['visible'] = false;
                }
                $notifications = $this->bulk_update_products($company, $data, $productIds, $seller, $extraParams);
            break;
            case 'delete':
                $storeProduct_repo = $this->entityManager->getRepository(Products::class);
                $storeHash = $request->get('storeHash');
                foreach ($productIds as $productId) {
                    $product = $storeProduct_repo->findOneBy(['id' => $productId, 'company' => $company]);
                    if (!empty($product)) {
                        list($tempnotifications, $deleted) = $this->delete_product($product->getProdId(), $product);

                        if ($deleted) {
                            $notifications[] = [
                                'type' => 'success',
                                'message' => $this->translate->trans(
                                    'message.product_delete_successfull',
                                    [
                                        'product_name' => $product->getName(),
                                        'product_id' => $productId,
                                    ]
                                ),
                            ];
                        } else {
                            $notifications[] = ['type' => 'danger', 'message' => $productId.' - Product does not exists'];
                        }
                    }
                }
            break;
            default:
                $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.invalid_bulk_action')];
            break;
        }

        return $notifications;
    }

    public function bulk_update_products($company , $data, $productIds, $seller, $extraParams = [])
    {
        $setting_repo = $this->entityManager->getRepository(SettingsValue::class);
        $params = [
            'settingName' => 'auto_approve_product',
            'company'     => $company,
        ];
        $setting = $setting_repo->findOneBy($params);
        $platformHelper = $this->getAppHelper('platform');

        $notifications = [];
        $is_success = true;

        foreach ($productIds as $pIds) {
            $allowedStatus = ['A', 'D', 'B', 'N'];

            if (!empty($seller)) {
                // do not allow block products
                $allowedStatus = ['A', 'D'];
            }
            $params = array(
                'item_per_page' => 1,
                'page' => 1,
                'ids' => $pIds,
                'statuses' => $allowedStatus,
            );
            
            list($storeProducts, $filters) = $this->get_products($params);
            $products = $storeProducts->getItems();
            if (empty($products)) {
                $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.can_not_perform_this_action')];
            }
            foreach ($products as $product) {
                $extraParams["fromBatchAction"] = TRUE;
                if(($product->getStatus() == 'D') and ($setting->getValue() == 'N') and (!empty($seller)))
                {
                    $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.can_not_perform_this_action')];
                } else {
                    list($product, $notification, $is_success) = $this->update_product($data, $product->getProdId(), $product, null, $extraParams);
                    if ($is_success) {
                        $notifications[] = array('type' => 'success', 'message' => $this->translate->trans('message.bulk_action.proceessed_successfully'));
                    } else {
                        $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.invalid_data_for_bulk_action')];
                    }
                }
            }

        }
        
        return $notifications;
    }

    public function delete_product($platform_product_id, $product)
    {
        $is_success = true;
        $notifications = [];
        $platformHelper = $this->getAppHelper('platform');
        $response = $platformHelper->delete_platform_product($platform_product_id);

        if (is_array($response) && isset($response[0])) {
            
            $response = json_decode($response[0]);

            if (
                isset($response->details) && isset($response->details->applicationError) && 
                isset($response->details->applicationError->code) &&
                strtoupper($response->details->applicationError->code) == "PRODUCT_NOT_FOUND"
            ) {
                $this->delete_store_product($product);
                $is_success = true;

            } else if (isset($response->message) && !empty($response->message)) {
                $notifications['danger'][] = $response->message;
                $is_success = false;

            } else {
                $this->delete_store_product($product);
                $is_success = true;
            }
        } else {
            $this->delete_store_product($product);
            $is_success = true;
        }
        $this->clear_all_product_caches($platform_product_id);

        return [$notifications, $is_success];
    }

    public function delete_store_product($product)
    {
        try {
            $storeProduct_repo = $this->entityManager->getRepository(Products::class);
            $productData = $storeProduct_repo->findOneBy(['id' => $product->getId()]);
            $productData->setIsDeleted((int)1);
        
            $this->entityManager->persist($productData);
            //$this->entityManager->remove($product);
            $this->entityManager->flush();
            
        } catch (DBALException $e) {
            $sql_error_code = $e->getPrevious()->getCode();
            if ($sql_error_code == '23000') {
                $this->add_notification(
                    'danger',
                    $this->container->get('translator')->trans(
                        'cannot_delete_product_already_in_use',
                        array(
                            'product_name' => $product->getName(),
                            'product_id' => $product->getId(),
                        )
                    )
                );
            } else {
                $this->add_notification(
                    'danger',
                    $this->container->get('translator')->trans(
                        'cannot_delete_product',
                        array(
                            'product_name' => $product->getName(),
                            'product_id' => $product->getId(),
                        )
                    )
                );
            }
        }

    }

    public function get_product_count($company, $seller)
    {
        $storeProduct_repo = $this->entityManager->getRepository(Products::class);
        $count = $storeProduct_repo->getProductCount($company, $seller);

        return $count;
    }

     public function arrangeUpdatePlatformProduct($data_collection, $extra_params = [])
    {
        $notifications = [];
        $platform_product_data['product'] = [];

        if (isset($data_collection['name']) && !empty($data_collection['name'])) {
            $platform_product_data['product']['name'] = $data_collection['name'];
        }

        if (isset($data_collection['productType']) && !empty($data_collection['productType'])) {
            $platform_product_data['product']['productType'] = $data_collection['productType'];
        }
        if (isset($data_collection['sku']) && !empty($data_collection['sku'])) {
            $platform_product_data['product']['sku'] = $data_collection['sku'];
        }

        if (isset($data_collection['price']) && !empty($data_collection['price'])) {
            $platform_product_data['product']['priceData']['price'] = $data_collection['price'];
        }

        if (isset($data_collection['weight']) && !empty($data_collection['weight'])) {
            $platform_product_data['product']['weight'] = (float) $data_collection['weight'];
        }

        if (isset($data_collection['description']) && !empty($data_collection['description'])) {
            $platform_product_data['product']['description'] = $data_collection['description'];
        }

        if (isset($data_collection['brand']) && !empty($data_collection['brand'])) {
            $platform_product_data['product']['brand'] = $data_collection['brand'];
        }
     
        if (
            isset($data_collection['discount']) && 
            isset($data_collection['discount_type']) && 
            !empty($data_collection['discount']) && 
            !empty($data_collection['discount_type'])
        ) {
            $platform_product_data['product']['discount'] = [
                "type" => $data_collection['discount_type'],
                "value" => $data_collection['discount']
            ];
        }
        if (isset($data_collection['visible'])) {
            $platform_product_data['product']['visible'] = $data_collection['visible'];
        }

        $platform_product_data['product']['seoData']['tags'] = [
            [
                "type" => "meta",
                "props" => [
                    "name" => "wk_wix_mp_product",
                    "content" => "wk_wix_mp_product"
                ]
            ]
        ];
        $platform_product_data['images'] = [];
        $commonHelper = $this->getHelper('common');
        //$thumbnail = false; // To set first image as product thumbnail
        // check for image_url
        if (isset($extra_params['image_url']) && !empty($extra_params['image_url'])) {
            foreach ($extra_params['image_url'] as $image_url) {
                //check if base64 data : drag&drop case
                $imageUrl = $this->base64ToUrl($image_url);
                if (!empty($imageUrl)) {
                    $platform_product_data['images'][] = [
                        'image_url' => $imageUrl,
                    ];
                }
            }
        }

        if (isset($data_collection['images']) && !empty($data_collection['images'])) {
            foreach ($data_collection['images'] as $image) {
                if ($image_url = $commonHelper->encode_base64_local_image($image)) {
                    $platform_product_data['images'][] = [
                        'image_url' => $image_url,
                        // 'is_thumbnail' => !$thumbnail
                    ];
                    // if (!$thumbnail) {
                    //     $thumbnail = true;
                    // }
                }
            }
        }
        return [$platform_product_data, $notifications];
    }


    public function arrangeUpdatePlatformProductVellum($data_collection, $extra_params = []) {
        $notifications = []; 
        $platform_product_data['product'] = [];

        if (isset($data_collection['name']) && !empty($data_collection['name'])) {
            $platform_product_data['product']['name'] = $data_collection['name'];
        }
    
        if (isset($data_collection['productType']) && !empty($data_collection['productType'])) {
            $platform_product_data['product']['productType'] = 'physical';
        }

        if (isset($data_collection['sku']) && !empty($data_collection['sku'])) {
            $platform_product_data['product']['sku'] = $data_collection['sku'];
        }

        if (isset($data_collection['price']) && !empty($data_collection['price'])) {
            $platform_product_data['product']['priceData']['price'] = $data_collection['price'];
        }

        if (isset($data_collection['weight']) && !empty($data_collection['weight'])) {
            $platform_product_data['product']['weight'] = (float) $data_collection['weight'];
        }

        $desc = [
            [
                '<b>How was the wine stored ?</b></br>',
                $data_collection['condition1'] == "Other" ? $data_collection['conditionAdd'] : $data_collection['condition1'],
            ],
            [
                '</br><b>Cap and Label condition ?</b></br>',
                 $data_collection['condition3'],
            ],
            [
                '</br><b>Filling level of the bottle ?</b></br>',
                $data_collection['condition2'],
            ],
            [
                '</br><b>Source of supply ?</b></br>',
                $data_collection['condition4'] == "Other" ? $data_collection['conditionFourthAdd'] :
                $data_collection['condition4'],
            ],
            [
                '</br>',
                $extra_params['extradetails'],
            ]
        ];
        $result = "";
        $counter = 0;
        foreach($desc as $item){
            if(empty($item[1])) {
                continue;
            }else{
                if($counter <= 3) {
                    $result .= "{$item[0]}\n";
                    $result .= "{$item[1]}\n\n";
                } else {
                    $result .= "{$item[1]}\n\n";
                }
            }
            $counter++;
        }
      
       
        $platform_product_data['product']['description'] = $result;
        

        if (isset($data_collection['brand']) && !empty($data_collection['brand'])) {
            $platform_product_data['product']['brand'] = $data_collection['brand'];
        }

        if (
            isset($data_collection['discount']) && 
            isset($data_collection['discount_type']) && 
            !empty($data_collection['discount']) && 
            !empty($data_collection['discount_type'])
        ) {
            $platform_product_data['product']['discount'] = [
                "type" => $data_collection['discount_type'],
                "value" => $data_collection['discount']
            ];
        }

        foreach ($data_collection['awards'] as $key => $award_data) {
            if ($award_data == "" ||  $award_data == "Select Awards") {
                continue;
            }
            $award[$key] = $award_data . " - " . $data_collection['awardsValue'][$key];
            
        }
        $data_collection['awards'] = isset($award) ? $award : [];
        $award_arr = [];
        $award_arr = array_merge($award_arr, $data_collection['awards']);
        $result_awards = "";
        foreach($award_arr as $awards) {
            $result_awards .= "{$awards}\n\n,"; 
        }

        if (isset($data_collection) && !empty($data_collection)) {
            $platform_product_data['product']['additionalInfoSections'] = [

                [
                    'title' => 'Grape Variety',
                    'description' => $data_collection['grape_varity'],
                ],
                [
                    'title' => 'Vintage',
                    'description' => $data_collection['vintage'],
                ],
                [
                    'title' => 'Awards',
                    'description' => $result_awards,
                ],
                [
                    'title' => 'BottleSize',
                    'description' => $this->bottleSize($data_collection['BottleSize'])
                ],
                [
                    'title' => 'Country',
                    'description' => $data_collection['country'],
                ],
                [
                    'title' => 'Region',
                    'description' => $data_collection['region'],
                ],
                [
                    'title' => 'Appellation',
                    'description' => $data_collection['appellation'],
                ],
                [
                    'title' => 'Classification',
                    'description' => $data_collection['classification'],
                ]
               
            ];

        }

        if (isset($data_collection['visible'])) {
            $platform_product_data['product']['visible'] = $data_collection['visible'];
        }
        $platform_product_data['product']['seoData']['tags'] = [
            [
                "type" => "meta",
                "props" => [
                    "name" => "wk_wix_mp_product",
                    "content" => "wk_wix_mp_product"
                ]
            ]
        ];
        $platform_product_data['images'] = [];
        $commonHelper = $this->getHelper('common');
        $thumbnail = false; // To set first image as product thumbnail
        // check for image_url
       
        if (isset($extra_params['image_url']) && !empty($extra_params['image_url'])) {
            foreach ($extra_params['image_url'] as $image_url) {
                //check if base64 data : drag&drop case
                $imageUrl = $this->base64ToUrl($image_url);
                if (!empty($imageUrl)) {
                    $platform_product_data['images'][] = [
                        'image_url' => $imageUrl,
                        'is_thumbnail' => !$thumbnail
                    ];
                    if (!$thumbnail) {
                        $thumbnail = true;
                    }
                }
            }
        }
       
        return [$platform_product_data, $notifications];
    }

    public function assignProductsToSeller($product_ids, $seller, $companyApplication)
    {
        $notifications = [];
        $product_repo = $this->entityManager->getRepository(Products::class);
        $totalproducts = count($product_ids);
        $updated = 0;
        $skipped = $totalproducts;
        try { 
            $updated = $product_repo->bulkUpdate($companyApplication->getCompany(), ['seller' => $seller], $product_ids);
            $skipped = $totalproducts - $updated;
            // $this->entityManager->flush();
        } catch (DBALException $e) {
            $notifications[] = ['type' => 'danger', 'message' => 'Error occured while assigining seller to products'];
        }

        $notifications[] = [
            'type' => 'success',
            'message' => $this->container->get('translator')->trans(
                'message.product.seller_assign.success_%s_result%__skip_%sk_result%',
                array(
                    's_result' => $updated,
                    'sk_result' => $skipped,
                )
            ),
        ];

        return $notifications;
    }

    public function add_bc_product($id, $companyApplication)
    {
        if (empty($id)) {
            return [];
        }
        $storeProduct_repo = $this->entityManager->getRepository(Products::class); 
        $product = $storeProduct_repo->findOneBy(['company' => $companyApplication->getCompany(), '_prod_id' => $id]);
        if ($product == null) {
            $platformHelper = $this->getAppHelper('platform');
            $platformHelper->init($companyApplication);
            list($platform_product, $notification) = $this->get_product($id,[], $platformHelper);
            
            $isWkProduct = false;
            if (!empty($platform_product) && isset($platform_product->seoData->tags)) {
                foreach ($platform_product->seoData->tags as $seoTags) {
                    if (isset($seoTags->type) && $seoTags->type == "meta") {
                        if (isset($seoTags->props->name) && $seoTags->props->name == "wk_wix_mp_product") {
                            $isWkProduct = true;
                        }
                    }
                }
            }
            
            if (!empty($platform_product) && isset($platform_product->productType) && $platform_product->productType != "digital" && !$isWkProduct) {

                $productImage = isset($platform_product->media->mainMedia->thumbnail->url) ? $platform_product->media->mainMedia->thumbnail->url : "";

                $product = new Products();
                $product->setProdId($platform_product->id);
                $product->setCompany($companyApplication->getCompany());
                $product->setTimestamp(time());
                $product->setName($platform_product->name);
                if (isset($platform_product->sku)) {
                    $product->setSku($platform_product->sku);
                }
                $product->setPrice($platform_product->price->price);
                $product->setImage($productImage);
                //product->setStockLevel($platform_product->inventory_level);
                // if (isset($platform_product->primary_image) && isset($platform_product->primary_image->thumbnail_url)) {
                //     $product->setImage($platform_product->primary_image->thumbnail_url);
                // } elseif (isset($platform_product->primary_image) && isset($platform_product->primary_image->url_thumbnail)) {
                //     $product->setImage($platform_product->primary_image->url_thumbnail);
                // } 
                $product->setStatus($platform_product->visible ? 'A' : 'D');
                $product->setTrackInventory($platform_product->stock->trackInventory);
                if($platform_product->stock->trackInventory) {
                    $product->setQuantity($platform_product->stock->quantity);
                }
                $product->setInStock($platform_product->stock->inStock);
                $this->entityManager->persist($product);
                $this->entityManager->flush();
            }
        }
    }

    public function sync_products($request, CompanyApplication $companyApplication)
    {
        $is_success = false;
        $notifications = [];
        $params = $request->request->all();
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }
        
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0
        $company = $companyApplication->getCompany();
        $product_repo = $this->entityManager->getRepository(Products::class);
        $product_count = $product_repo->getProductCount($company);
        $existingProductCount = isset($product_count[1]) ? $product_count[1] : 0; // already added products count
        $plan_prduct_count = 0; //initialize plan product count
        if ($companyApplication->getSubscription() != null) {
            $plan_features = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
            $plan_prduct_count = isset($plan_features['max_products']) ? $plan_features['max_products'] : 0;
        }
        if ($plan_prduct_count != 0 && $plan_prduct_count <= $existingProductCount) {
            $notifications[] = ['message' => 'Plan Quota exhausted, Please upgrade your plan first', 'type' => 'danger'];

            return array(
                'totalCount' => 0, /* no need to paginate further  */
                'items' => [],
                'notifications' => $notifications,
            );
        }
        $result = ['added' => 0, 'skipped' => 0];

        if ($start == 0) {
            //clear cache if exists to prevent duplicacy
            $this->cache->deleteitem('products_data_'.$company->getId());
        }
        // check in cache
        $hasCacheProductData = $this->cache->hasItem('products_data_'.$company->getId());
        //get cahce id
        $cacheProductData = $this->cache->getItem('products_data_'.$company->getId());
        $productsData = [];
        $requestApi = false;
        if (!$hasCacheProductData) {
            $requestApi = true;
            $productsData = array(
                'totalCount' => 0,
                'items' => [],
                'page' => 1,
            );
        } else {
            $productsData = $cacheProductData->get();
        }
        $batch = (int) $params['limit'] + (int) $start;
        if ($batch > $productsData['totalCount']) {
            $batch = $productsData['totalCount'];
        }
        
        // check if batch requested is not in cache : requestApi
        if ($requestApi || (($batch <= $productsData['totalCount']) && ($batch > count($productsData['items'])))) {
            //either product data is empty or less then required
            //request API
            $platformHelper = $this->getAppHelper('platform');
            $apiParams = [
                'query' => [
                    'paging' => [
                        'limit' =>  $params['limit'],
                        'offset' => $params['page'] - 1,
                    ]
                ],
                'includeHiddenProducts' => true
                
            ];
            if ($params['page'] > 1) {
                $apiParams = [
                    'query' => [
                        'paging' => [
                            'limit' => $params['limit'],
                            'offset' =>($params['page']-1) * $params['limit'],
                        ]
                    ],
                    'includeHiddenProducts' => true
                ];
            }
            list($response, $error) = $platformHelper->get_platform_products(
                $apiParams
            );
            //$response = isset($response[0]) ? json_decode($response[0]) : (Object) Array();
            $response = json_decode($response);
            if (isset($response->products) && !empty($response->products)) {
                $productsData['items'] = array_merge($productsData['items'], $response->products);
                ++$productsData['page'];
                $productsData['totalCount'] = isset($response->totalResults) ? $response->totalResults : count($response->products);
                
                //save to cache
                $cacheProductData->set($productsData);
                $isSaved = $this->cache->save($cacheProductData);
            } else {
                if(!empty($response->details->validationError)){
                    $notifications[] = ['message' => $response->details->validationError->fieldViolations[0]->description, 'type' => 'danger'];
                } else{
                    if(!empty($response->details->validationError)){
                        $notifications[] = ['message' => 'It is not possible to sync more than 10000 items', 'type' => 'danger'];
                    } else{
                        $notifications[] = array(
                            'type' => 'danger',
                            'message' => $this->translate->trans('message.wix.product.unable_to_import_product'),
                        );
                    }
                }
            }
        }
        
        // process batch
        $toProcessProducts = array_slice($productsData['items'], $start, $params['limit']);
        //$toProcessProducts = $productsData['items'];
        if (!empty($toProcessProducts)) {
            foreach ($toProcessProducts as $platform_product) {
                if ($plan_prduct_count == 0 || $plan_prduct_count > $existingProductCount) {
                    $isProductExists = $product_repo->isProductExists($company, $platform_product->id);
                    if (empty($isProductExists) && isset($platform_product->productType) && $platform_product->productType != 'digital') {
                        ++$result['added'];

                        $productImage = isset($platform_product->media->mainMedia->thumbnail->url) ? $platform_product->media->mainMedia->thumbnail->url : "";

                        $product = new Products();
                        $product->setProdId($platform_product->id);
                        $product->setCompany($company);
                        $product->setTimestamp(time());
                        $product->setName($platform_product->name);
                        $product->setSku(isset($platform_product->sku) ? $platform_product->sku : "");
                        $product->setPrice(isset($platform_product->price->price) ? $platform_product->price->price : 0);
                        $product->setImage($productImage);
                        //$product->setStockLevel($platform_product->inventory_level);
                        // if (!empty($platform_product->primary_image)) { // in case primary image is null
                        //     if (isset($platform_product->primary_image->thumbnail_url)) {
                        //         $product->setImage($platform_product->primary_image->thumbnail_url);
                        //     } elseif (isset($platform_product->primary_image->url_thumbnail)) {
                        //         $product->setImage($platform_product->primary_image->url_thumbnail);
                        //     }
                            
                        // }
                        $product->setStatus($platform_product->visible ? 'A' : 'D');
                        $product->setTrackInventory($platform_product->stock->trackInventory);
                        if($platform_product->stock->trackInventory) {
                            $product->setQuantity($platform_product->stock->quantity);
                        }
                        $product->setInStock($platform_product->stock->inStock);
                        $this->entityManager->persist($product);
                        // one more product added
                        ++$existingProductCount;
                    } else {
                        ++$result['skipped'];
                    }
                } else {
                    $notifications[] = ['message' => 'Plan Quota exhausted, Please upgrade your plan first', 'type' => 'danger'];
                    break; // limit exhust no need to loop for rest of the products
                }
            }
            $this->entityManager->flush();
        }
        if(!empty($response->products)) {
            $notifications[] = [
                'message' => $this->container->get('translator')->trans(
                    'message.product.sync.success_%s_result%__skip_%sk_result%',
                    array(
                        's_result' => $result['added'],
                        'sk_result' => $result['skipped'],
                    )
                ),
                'type' => 'success',
            ];
        }
        // clear cache on last batch
        if ($batch >= $productsData['totalCount']) {
            $this->cache->deleteitem('products_data_'.$company->getId());
        }

        return array(
            'totalCount' => $productsData['totalCount'],
            'items' => $toProcessProducts,
            'notifications' => $notifications,
        );
    }

    public function update_wix_product($id, $companyApplication)
    {
        $company = $companyApplication->getCompany();
        $storeProduct_repo = $this->entityManager->getRepository(Products::class);
        $product = $storeProduct_repo->findOneBy(['company' => $company, '_prod_id' => $id]);
        
        if ($product != null) {
            $platformHelper = $this->getAppHelper('platform');
            $platformHelper->init($companyApplication);
            list($platform_product, $notification) = $this->get_product($id,[], $platformHelper);
            if (!empty($platform_product)) {    
                $productImage = isset($platform_product->media->mainMedia->thumbnail->url) ? $platform_product->media->mainMedia->thumbnail->url : "";
                
                $product->setName($platform_product->name);
                //$product->setStockLevel($platform_product->inventory_level);
                $product->setSku(isset($platform_product->sku) ? $platform_product->sku : "");
                $product->setPrice(isset($platform_product->price->price) ? $platform_product->price->price : 0);
                $product->setImage($productImage);
                // do not update status for under review or block products 
                if ($product->getStatus() == 'A' || $product->getStatus() == 'D') {
                    if (isset($platform_product->visible) && $platform_product->visible == true) {
                        $product->setStatus('A');
                    } elseif (isset($platform_product->visible) && $platform_product->visible == false) {
                        $product->setStatus('D');
                    }
                }
                // if (isset($platform_product->primary_image) && isset($platform_product->primary_image->thumbnail_url)) {
                //     $product->setImage($platform_product->primary_image->thumbnail_url);
                // } elseif (isset($platform_product->primary_image) && isset($platform_product->primary_image->url_thumbnail)) {
                //     $product->setImage($platform_product->primary_image->url_thumbnail);
                // } 
                $product->setTimestamp(strtotime($platform_product->lastUpdated));
                $product->setTrackInventory($platform_product->stock->trackInventory);
                if($platform_product->stock->trackInventory) {
                    $product->setQuantity($platform_product->stock->quantity);
                }
                $product->setInStock($platform_product->stock->inStock);
            }
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->clear_all_product_caches($product->getId(), $companyApplication);
        }
    }

    public function clear_all_product_caches($storeProductId = 0, $companyApplication = null)
    {
        if ($companyApplication == null) {
            $companyApplication = $this->container->get('app.runtime')->get_company_application();
        }
        // $this->cache->deleteItem('product_custom_field'.'_'.$storeProductId.$companyApplication->getId());
        // $this->container->get('cache.app')->deleteItem('product_data_'.$storeProductId.'_custom_fied');
        // $this->cache->deleteItem('categories_'.$companyApplication->getCompany()->getId());
        // $this->cache->deleteItem('product_data_'.$storeProductId.'_images');
        // $this->cache->deleteItem('tax_classes_'.$companyApplication->getCompany()->getId());
        // $this->cache->deleteItem('brand_list_'.$companyApplication->getCompany()->getId());
        // $this->cache->deleteItem('product_data_'.$storeProductId.'_videos');
        // $this->cache->deleteItem('product_data_'.$storeProductId.'_custom_info');
        
        $this->cache->deleteItem('product_data_'.$storeProductId);
        //dd($productCacheList = $this->cache->getItem('product_data_'.$storeProductId));
    }

    public function base64ToUrl($image_url)
    {
        $commonHelper = $this->getHelper('common');
        $imageUrl = $image_url; //to return original url in case not base64 image
        //check if base64 data : drag&drop case
        if (preg_match("/^data:image\/(?<extension>(?:png|gif|jpg|jpeg));base64,(?<image>.+)$/", $image_url)) {
            $bs64ToUrl = $commonHelper->base64_to_url($image_url);
            if ($bs64ToUrl != false) {
                $imageUrl = $bs64ToUrl;
            }
        }

        return $imageUrl;
    }

    public function delete_product_image($platform_product_id, $image_id)
    {
        $is_success = false;
        $notifications = [];
        $platformHelper = $this->getAppHelper('platform');
        $response = $platformHelper->delete_platform_product_media($platform_product_id, [
            'mediaIds' => [
                $image_id
            ]
        ]);
        
        if (is_array($response) && isset($response[0])) {
            $is_success = true;

            // DELETE FROM OUR END
            


            $this->clear_all_product_caches($platform_product_id);
        } else {
            $notifications['danger'][] = "Image Doesn't deleted !!";
        }
        return [$notifications, $is_success];
    }

    public function clear_cache($params = [])
    {
        if (empty($this->cache) || true) {
        }
        if (isset($params['clear_store_data']) && $params['clear_store_data']) {
            $this->cache->deleteItem('categories_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
            $this->cache->deleteItem('tax_classes_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        }
        if (isset($params['platform_product_id']) && $params['platform_product_id']) {
            $platform_product_id = $params['platform_product_id'];
            if (isset($params['clear_all']) && $params['clear_all']) {
                $this->cache->deleteItem('product_data_'.$platform_product_id.'_images');
                $this->cache->deleteitem('product_data_'.$platform_product_id);
                $this->cache->deleteItem('product_data_'.$platform_product_id.'_custom_fied');
            } else {
                if (isset($params['clear_images']) && $params['clear_images']) {
                    $this->cache->deleteItem('product_data_'.$platform_product_id.'_images');
                }
                if (isset($params['custom_fieds']) && $params['custom_fieds']) {
                    $this->cache->deleteItem('product_data_'.$platform_product_id.'_custom_fied');
                }
                if (isset($params['product_data']) && $params['product_data']) {
                    $this->cache->deleteitem('product_data_'.$platform_product_id);
                }
                if (isset($params['clear_videos']) && $params['clear_videos']) {
                    $this->cache->deleteItem('product_data_'.$platform_product_id.'_videos');
                }
            }
        }
        if(isset($params['clear_brands']) && !empty($params['clear_brands'])){
            $this->cache->deleteItem('brand_list_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        }

        return true;
    }

    public function sync_collections($request, $companyApplication, $onlyParent = false, $allowedCategories = [])
    {
        $company = $companyApplication->getCompany();
        $platformHelper = $this->getAppHelper('platform');
        $hasCategoriesTree = $this->cache->hasItem('collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        $categoriesTreeCacheList = $this->cache->getItem('collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());

        $catTree = [];
        $category_list = [];

        $params = $request->request->all();
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }
        
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0

        //$params['limit'] = 100;
        //$params['page']  = 1;

        $catTree = [];

        $result = ['added' => 0, 'skipped' => 0];

        if ($start == 0) {
            //clear cache if exists to prevent duplicacy
            $this->cache->deleteitem('collection_data_'.$company->getId());
            $this->cache->deleteitem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        }

        $requestApi = false;
        
        if (!$hasCategoriesTree) {
            $requestApi = true;
            $catTree = array(
                'totalCount' => 0,
                'items' => [],
                'page' => 1,
            );
        } else {
            $catTree = $categoriesTreeCacheList->get();
        }
        
        $batch = (int) $params['limit'] + (int) $start;
        if ($batch > $catTree['totalCount']) {
            $batch = $catTree['totalCount'];
        }
        if ($requestApi || (($batch <= $catTree['totalCount']) && ($batch > count($catTree['items'])))) {
            $apiParams = [
                'query' => [
                    'paging' => [
                        'limit' =>  $params['limit'],
                        'offset' => $params['page'] - 1,
                    ]
                ]
            ];
            if ($params['page'] > 1) {
                $apiParams = [
                    'query' => [
                        'paging' => [
                            'limit' => $params['limit'],
                            'offset'=> ($params['page'] - 1) * $params['limit'],
                        ]
                    ]
                ];
            } 
            list($catTreeResponse, $error) = $platformHelper->get_platform_categories($apiParams);
            $response = json_decode($catTreeResponse); 
            if (isset($response->collections) && !empty($response->collections)) {

                if ($params['page'] == 1) {
                    $collection_repo = $this->entityManager->getRepository(Collections::class);
                    // $collection_repo->removeAllCollections($company);
                }

                $catTree['items'] = array_merge($catTree['items'], $response->collections);
                ++$catTree['page'];
                $catTree['totalCount'] = isset($response->totalResults) ? $response->totalResults : count($response->collections);
                
                //save to cache
                $categoriesTreeCacheList->set($catTree);
                $isSaved = $this->cache->save($categoriesTreeCacheList);
                $categoriesTreeCacheList->expiresAfter(120);
            } else {
                $notifications[] = array(
                    'type' => 'danger',
                    'message' => $this->translate->trans('message.wix.product.unable_to_import_collections'),
                );
            }
        }
        $collection_repo = $this->entityManager->getRepository(Collections::class);
        // // process batch
        $categories = $collection_repo->getCollections(['get_all_results' => 1], $company);
        // $categories = $collection_repo->findAll($company);
        foreach($catTree['items'] as $items) {
            if($items->id == "00000000-000000-000000-000000000001") {
                continue;
            } else {
                $products_client_ids[] = $items->id;
            }
        }
        
        $hasDeletedCategoty = $this->cache->hasItem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        $deletedCategoriesCacheList = $this->cache->getItem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        
        if (!$hasDeletedCategoty) {
              $deletedCategory = [];
        } else {
            $deletedCategory = $deletedCategoriesCacheList->get();
        }

        foreach($categories  as $category) {
            if(!in_array($category->getCollectionId(),$products_client_ids)) {
                array_push($deletedCategory, $category->getCollectionId());

               $deletedCategoriesCacheList->set($deletedCategory);
               $isSaved = $this->cache->save($deletedCategoriesCacheList);
                 
            } else {
                continue;
            }
        }
        $toProcessCollections = array_slice($catTree['items'], $start, $params['limit']);
    
        //$toProcessProducts = $productsData['items'];
        if (!empty($toProcessCollections)) {
            foreach ($toProcessCollections as $platform_collection) {
                //if ($plan_prduct_count == 0 || $plan_prduct_count >= $existingProductCount) {
                if($platform_collection->id != "00000000-000000-000000-000000000001") {
                    $isCollectionExists = $collection_repo->isCollectionExists($companyApplication->getCompany(), $platform_collection->id);
                    if(isset($isCollectionExists)) {
                        $name_data = $collection_repo->findOneBy(['id' => $isCollectionExists['id']]);
                        $name = $name_data->getName();
                        $is_name_updated = $name != $platform_collection->name ? $platform_collection->name : "";
                        if(!empty($is_name_updated)) {
                          $name_data->setName($is_name_updated);
                        }
                    }
               
                    if (empty($isCollectionExists) && isset($platform_collection->id)) {
                        ++$result['added'];
                        $collections = new Collections();
                        $collections->setName($platform_collection->name);
                        $collections->setCollectionId($platform_collection->id);
                        $collections->setCompany($company);
                        
                        $this->entityManager->persist($collections);
                        // one more product added
                    } else {
                        ++$result['skipped'];
                    }
                }
                // } else {
                //     $notifications[] = ['message' => 'Plan Quota exhausted, Please upgrade your plan first', 'type' => 'danger'];
                //     break; // limit exhust no need to loop for rest of the products
                // }
            }
            $this->entityManager->flush();
        }
        $notifications[] = [
            'message' => $this->container->get('translator')->trans(
                'message.collection.sync.success_%s_result%__skip_%sk_result%',
                array(
                    's_result' => $result['added'],
                    'sk_result' => $result['skipped'],
                )
            ),
            'type' => 'success',
        ];
        
        //clear cache on last batch
        if ($batch >= $catTree['totalCount']) {
            $this->cache->deleteitem('collection_data_'.$company->getId());
            $deletedData = $this->cache->getItem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId())->get();
            if($hasDeletedCategoty){
                foreach ($deletedData as $key => $value) {
                    list($response,$error) = $platformHelper->get_platform_categorie(['id'=>$value]);
                    $categoryData = json_decode($response);
                        if(!isset($categoryData->collection)){
                            $cat = $collection_repo->findOneBy(['_collectionId' => $value , 'company' => $company->getId()]);
                            if(isset($cat)) {
                            $this->entityManager->remove($cat);
                            $this->entityManager->flush();
                        }  

                    }
                }
            }

            $this->cache->deleteitem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
            
        }
        return array(
            'totalCount' => $catTree['totalCount'],
            'items' => $toProcessCollections,
            'notifications' => $notifications,
        );
        
        // if (!empty($catTree) && isset($catTree['data'])) {
        //     $category_list = $catTree['data'];
        // }
    }

    public function sync_collections_cache($request, $companyApplication){
        
        $company = $companyApplication->getCompany();
        $hasDeletedCategoty = $this->cache->hasItem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        $deletedCategoriesCacheList = $this->cache->getItem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        $platformHelper = $this->getAppHelper('platform');
        $collection_repo = $this->entityManager->getRepository(Collections::class);
        if($hasDeletedCategoty){
            foreach ($deletedCategoriesCacheList->get() as $key => $value) {
            list($response,$error) = $platformHelper->get_platform_categorie(['id'=>$value]);
                $categoryData = json_decode($response);
                    if(!isset($categoryData->collection)){
                        $cat = $collection_repo->findOneBy(['_collectionId' => $value , 'company' => $company->getId()]);
                        if(isset($cat)) {
                        $this->entityManager->remove($cat);
                        $this->entityManager->flush();
                    }  

                }
            }
        }

        $this->cache->deleteitem('deleted_collection_data_'.$this->container->get('app.runtime')->get_company_application()->getCompany()->getId());
        return 'ok';
        
    }

    public function assignCategoriesToProducts(
        $product, $productIds = [], $categories = []
    ) 
    {   //dump($product, $productIds, $categories);
        $oldCategories = $product->getCategoryData();
        $toRemoveCat = (!empty($oldCategories)) ? array_diff($oldCategories, $categories) : [];
        $platformHelper = $this->getAppHelper('platform');
        //dump($oldCategories, $toRemoveCat, $productIds);   
        if (count($productIds) == 1) {

            // Remove categories from the single product
            foreach($toRemoveCat as $categoryId) {
                $params = [
                    'productIds' => $productIds
                ];
                $response = $platformHelper->remove_products_from_collection($categoryId, $params);
            }
            
            //Add Categories to single Product
            foreach($categories as $categoryId) {
                $params = [
                    'productIds' => $productIds
                ];
                $platformHelper->add_products_to_collection($categoryId, $params);
            }
        }
    }

    public function createCollections($name = "")
    {
        $platformHelper = $this->getAppHelper('platform');
        $apiParams = [
                'collection' => [
                    "name" => $name
                ]
            ];
        list($catTreeResponse, $error) = $platformHelper->create_collections($apiParams);
        return $catTreeResponse;
    }

    public function getCategoryTree($companyApplication, $onlyParent = false, $allowedCategories = [])
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $collection_repo = $this->entityManager->getRepository(Collections::class);
        $collections = $collection_repo->getCollections(['get_all_results' => 1], $companyApplication->getCompany());

        $collectionList = [];
        foreach($collections as $collection) {
            $collectionList[$collection->getCollectionId()] = $collection;
        }

        $allowedCategoryList = [];
        if (!empty($allowedCategories)) {
            foreach ($allowedCategories as $allowedCategoryId) {
                if (isset($collectionList[$allowedCategoryId])) {
                    $allowedCategoryList[$allowedCategoryId] = $collectionList[$allowedCategoryId];
                }
            }
            $collectionList = $allowedCategoryList;
        }

        return $collectionList;
    }

    public function getProductFields($is_admin = false)
    {
        $fields = [
            'handleId' => [
                'field_id' => 'handleId',
                'field_label' => 'handleId',
                //'is_primary' => true,
            ],
            'name' => [
                'field_id' => 'name',
                'field_label' => 'name',
                'is_primary' => true,
            ],
            'collection' => [
                'field_id' => 'collection',
                'field_label' => 'collection',
                //'is_primary' => true,
            ],
            'fieldType' => [
                'field_id' => 'fieldType',
                'field_label' => 'fieldType',
                'is_primary' => true,
            ],
            'seller_id' => [
                'field_id' => 'seller_id',
                'field_label' => 'seller_id',
            ],
            'price' => [
                'field_id' => 'price',
                'field_label' => 'price',
                'is_primary' => true,
            ],
            'brand' => [
                'field_id' => 'brand_id',
                'field_label' => 'brand',
            ],
            'weight' => [
                'field_id' => 'weight',
                'field_label' => 'weight',
            ],
            'sku' => [
                'field_id' => 'sku',
                'field_label' => 'sku',
                //'is_primary' => true,
            ],
            'description' => [
                'field_id' => 'description',
                'field_label' => 'description',
            ],
            'productImageUrl' => [
                'field_id' => 'productImageUrl',
                'field_label' => 'productImageUrl',
            ],
            'visible' => [
                'field_id' => 'visible',
                'field_label' => 'visible',
            ],
        ];

        return $fields;
    }

    public function handle_import_req($request, $formData, $companyApplication)
    {
        $params = $request->request->all();
        $notifications = [];
        $company = $companyApplication->getCompany();
        $seller = $formData['seller'] ?? null;
        $form_data = $formData['form_data'] ?? [];
        $file = $formData['file'];
        $skipExistingProducts = isset($form_data['skip_existing_products']) ? $form_data['skip_existing_products'] : false;
        if ($form_data['delimiter'] == 'C') {
            $delimiter = ',';
        } elseif ($form_data['delimiter'] == 'T') {
            $delimiter = "\t";
        } else {
            $delimiter = ';';
        }
        $enclosure = '"';
        // $requiredCsvFields = array(
        //     'id',
        //     'categories',
        //     'price',
        //     'type',
        //     'sku',
        //     'inventory_level',
        // );
        $import_result = [
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
            'notifications' => [],
        ];
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0

        if (empty($file) || $file->getError() > 0) {
            $notifications[] = ['type' => 'danger', 'message' => $file->getErrorMessage()];

            return array(
                'totalCount' => 0,
                'items' => [],
                'notifications' => $notifications,
            );
        }
        $originalFileName =str_replace(array(' ', '{', '}', '(', ')', ':', '\\', '/', '*', '@'), '-', $file->getClientOriginalName()); 
        $fileName = $file->getPathname();
        $cacheId = $company->getId().'_product_csv_'.$originalFileName;
        if (!empty($seller)) {
            $cacheId = $seller->getId().'_'.$cacheId;
        }
        $hasInCache = $this->cache->hasItem($cacheId);
        $cacheProductData = $this->cache->getItem($cacheId);
        if (!$hasInCache) {
            try {
                $count = 0;
                $fields = [];
                $fileData = [];
                $file = fopen($fileName, 'r');
                $formatFields = [
                    'type' => 'lower',
                ];
                
                while (($column = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
                    if ($count > 0) {
                        if (count($column) != count($fields)) {
                            $notifications[] = ['type' => 'danger', 'message' => 'Invalid CSV file, Please check the file. Some of the required columns are not exists'];
                            continue;
                        } 
                        $column = array_combine($fields, $column);
                        // check item_type
                        if (strtolower($column['fieldType']) != 'product') {
                            // is aproduct variant
                            // if (isset($fileData[$count])) {
                            //     if (!isset($fileData[$count]['variants'])) {
                            //         $fileData[$count]['variants'] = [];
                            //     }
                            //     $fileData[$count]['variants'][] = $column;
                            // }
                            // continue; // do not add to product array : already added to product variant
                        } else {
                            // format data 
                            foreach($formatFields as $formatFieldKey => $formatFieldType) {
                                if (isset($column[$formatFieldKey]) && !empty($column[$formatFieldKey])) {
                                    switch ($formatFieldType) {
                                        case 'lower' :
                                            $column[$formatFieldKey] = strtolower($column[$formatFieldKey]);
                                            break;
                                        case 'ucwords':
                                            $column[$formatFieldKey] = ucwords(strtolower($column[$formatFieldKey]));
                                            break;
                                        default:
                                            break;
                                    }
                                }                                
                            }
                            ++$count;
                        }
                    } else {
                        $fields = $column;
                        // if (!empty(array_diff($requiredCsvFields, $fields))) {
                        //     // invalid csv file
                        //     $notifications[] = ['type' => 'danger', 'message' => 'Invalid CSV file, Please check the file.Some of the required columns are not exists'];
                        //     break;
                        // }
                        ++$count;
                        continue;
                    }
                    $fileData[$count] = $column;
                }
                fclose($file);
                
                $productCsvData = array(
                    'fields' => $fields,
                    'items' => $fileData,
                    'page' => 1,
                    'totalCount' => count($fileData),
                );
                // save in cache
                $cacheProductData->set($productCsvData);
                $this->cache->save($cacheProductData);
            } catch (\Exception $e) {
                $notifications[] = ['type' => 'danger', 'message' => $e->getMessage()];

                return array(
                    'totalCount' => 0,
                    'items' => [],
                    'notifications' => $notifications,
                );
            }
        } else {
            $productCsvData = $cacheProductData->get();
        }
        $batch = (int) $params['limit'] + (int) $start;
        if ($batch > $productCsvData['totalCount']) {
            $batch = $productCsvData['totalCount'];
        }
        if ($start <= $batch) { // prevent overflow : Never occur
            // process batch
            $toProcessProducts = array_slice($productCsvData['items'], $start, $params['limit']);
            // category list sorted
            //$this->categoryList = $this->getCategoryTree($companyApplication);
            foreach ($toProcessProducts as $product) {
                //$product = array_combine($productCsvData['fields'], $product);
                list($import_result, $isStop) = $this->import_update_product($product, $companyApplication, $seller, $skipExistingProducts);
                $notifications = array_merge($notifications, $import_result);

                if ($isStop) { 
                    $this->cache->deleteitem($cacheId);
                    return array(
                        //'totalCount' => $productCsvData['totalCount'],
                        'notifications' => $notifications,
                        //'items' => [],
                        'isStop' => $isStop,
                        'active' => true,
                        //'error' => true
                    );
                }
            }
        }
        // clear cache on last batch
        if ($batch >= $productCsvData['totalCount']) {
            $this->cache->deleteitem($cacheId);
        }

        return array(
            'totalCount' => $productCsvData['totalCount'],
            'notifications' => $notifications,
            'items' => [],
        );
    }

    public function import_update_product($productData, $companyApplication, $seller = null, $skipExistingProducts = true)
    {
        $company = $companyApplication->getCompany();
        $this->storeProduct_repo = $this->entityManager->getRepository(Products::class);

        $commonHelper = $this->container->get('app.runtime')->getHelper('common');
        $isProductAutoApprove = $commonHelper->get_section_setting_value(['sectionName' => 'seller', 'settingName' => 'auto_approve_product', 'company' => $companyApplication->getCompany()]);
        
        $handleId = isset($productData['handleId']) ? $productData['handleId'] : (isset($productData['﻿handleId']) ? $productData['﻿handleId'] : "");
        $handleId = explode("product_", $handleId);
        $handleId = (!empty($handleId) && isset($handleId[1]) ) ? $handleId[1] : "";
        $product = $this->storeProduct_repo->findOneBy(['company' => $company, '_prod_id' => $handleId]);
        
        $catalogEvent = new CatalogEvent($companyApplication); 
        $catalogEvent->setProductData($productData);
        //$catalogEvent->setProductParams($extra_params);
        if ($product != null) {
            $event_action = 'update';
        } else {
            $event_action = 'add';
        }
        if ($seller != null) {
            $seller_or_admin = 'seller';
        } else {
            $seller_or_admin = 'admin';
        }
        // seller check
        // get seller by id
        if ($seller != null) {
            $allow = $this->verify_seller_product_import($seller, $product, $productData);
            if (!$allow) {
                $notifications[] = [
                    'message' => $this->translate->trans(
                        'message.product.import.error.seller%pid',
                        array(
                            'pid' => $productData['name'],
                        )
                    ),
                    'type' => 'danger',
                ];

                return [$notifications, false];
            }
        }
        $eventConstant = 'wix.catalog.product.'.$seller_or_admin.'.'.$event_action;
        if ($seller_or_admin == "seller") { 
            $eventConstant = 'catalog.product.wix.'.$seller_or_admin.'.'.$event_action;
        }
        $this->container->get('event_dispatcher')->dispatch($catalogEvent, $eventConstant);
        $productData = $catalogEvent->getProductData();
        if ($catalogEvent->getActionAllowed() == "N") {
            $notifications[] = [
                'message' => $this->_trans('wix.allowed.product.reached'),
                'type' => 'danger',
            ];
            
            return [$notifications, TRUE];
        }
        $csvProduct = $this->filterCsvProductForPlatform($productData, $companyApplication); 
        $platform_product = null;
        if (empty($product)) {
            $platform_product_id = 0;
        } else {
            // check if update allowed
            if ($skipExistingProducts) {
                //  for now : we skip product if already exists
                $notifications[] = [
                    'message' => $this->translate->trans(
                        'message.product.import.exists%pid',
                        array(
                            'pid' => isset($productData['name']) ? $productData['name'] : "",
                        )
                    ),
                    'type' => 'danger',
                ];
                
                return [$notifications, FALSE];
            } 
            // update product
            $platform_product_id = $product->getProdId();
            $params = [];
            list($platform_product_raw) = $this->get_product($platform_product_id, $params);
            if (!empty($platform_product_raw)) {
                $platform_product = json_decode(json_encode($platform_product_raw), true);
            }
        }

        $isUnderReview = FALSE;
        if(!empty($seller) && (isset($isProductAutoApprove) && $isProductAutoApprove == 'N')){
            $isUnderReview = TRUE;
        }
        // set seller  as in csv product
        if (!empty($productData['seller_id'])) {
            $sellerHelper = $this->getAppHelper('seller');
            $seller = $sellerHelper->get_seller(array('company' => $company, 'id' => $productData['seller_id']));
            if (!empty($product)) {
                $product->setSeller($seller);
            }
        }
        list($platform_product_data, $notifications) = $this->arrangeUpdateCsvProduct($csvProduct); 
       
        if(isset($isUnderReview) && $isUnderReview == TRUE){
            //$platform_product_data['status_to'] = 'N';
            $extraParams['status_to'] = 'N';
        }
        
        $updated = false;
        $fromCSV = true;
        if (empty($notifications)) {
            $responseNotifications = [];
            
            if (!empty($platform_product_id)) {
                list($product, $notifications, $updated, $productResponse) = $this->update_product($platform_product_data, $platform_product_id, $product, $seller);
                
            } else {
                $extraParams['categories'] = isset($platform_product_data['categories']) ? $platform_product_data['categories'] : [];
                $extraParams['fromCsv'] = TRUE;
                $platform_product_data['productType'] = 'physical';
                list($platform_product_data, $notifications) = $this->arrangeUpdatePlatformProduct($platform_product_data);
                list($updated, $productResponse, $product) = $this->create_product(
                    $companyApplication, $platform_product_data, $productData, $seller, $extraParams
                );
                if (!$updated && isset($productResponse->message)) {
                    $notifications[] = [
                        "message" => ucfirst($productResponse->message),
                        "type" => "danger"
                    ];
                }
            }
            
            if ($updated) {
                $productData['mp_product_id'] = $product->getId();
                // delete cache for this product :
                $this->cache->deleteItem('product_data_'.$product->getProdId());
                $this->cache->deleteItem('product_data_'.$product->getProdId().'_images');
                // create options and variants
                $createdProductId = $product->getProdId();
            }
        }
        
        $errorMessage = [];
        if (!empty($notifications)) {
            foreach ($notifications as $type => $typeNotifications) {
                
                if (isset($typeNotifications['message']) && 
                    ( $type == "danger" || 
                        (isset($typeNotifications['type']) && $typeNotifications['type'] == "danger")
                    )
                ) {
                    $errorMessage[] = $typeNotifications['message'];
                } elseif ($type != "success") {
                    // backward format compatible
                    foreach ($typeNotifications as $notification) {
                        $errorMessage[] = $notification;
                    }
                }
            }
            $notifications = [];
        } 
        
        // add/update notification
        if ($updated) {
            if ($platform_product_id) {
                $notifications[] = [
                    'message' => $this->translate->trans(
                        'message.product.import.updated%pid',
                        array(
                            'pid' => $productData['name'],
                        )
                    ),
                    'type' => 'success',
                ];
            } else {
                $notifications[] = [
                    'message' => $this->translate->trans(
                        'message.product.import.created%pid',
                        array(
                            'pid' => $productData['name'],
                        )
                    ),
                    'type' => 'success',
                ];
            }
        }
        if (!empty($errorMessage)) {
            $notifications[] = [
                'message' => $this->translate->trans(
                    'message.product.import.failed%pid_%error_message',
                    array(
                        'pid' => $productData['name'],
                        'error_message' => implode('<br/>', $errorMessage),
                    )
                ),
                'type' => 'danger',
            ];
        }
        
        return [$notifications, FALSE];
    }

    public function filterCsvProductForPlatform($product, $companyApplication)
    {
        $ignoreFields = array('seller_id', '﻿handleId');
        foreach ($ignoreFields  as $ignoreField) {
            if (isset($product[$ignoreField])) {
                unset($product[$ignoreField]);
            }
        }
        
        $bool_allowed = [
            'visible',
        ];
        
        foreach ($bool_allowed as $value) {
            if (isset($product[$value]) && strtolower($product[$value]) == "true") {
                $product[$value] = true;
            } else {
                $product[$value] = false;
            }
        }
        $product['custom_info'] = [];
        $serializeFields = array('collection', 'productImageUrl');
        foreach ($serializeFields as $serializeField) {
            switch ($serializeField) {
                case 'productImageUrl':
                    $images = [];
                    if (isset($product[$serializeField]) && !empty($product[$serializeField])) {
                        $pImages = explode(';', $product[$serializeField]);
                        $isDefMaked = FALSE;
                        foreach ($pImages as $imgeUrl) {
                            
                            $images[] = [
                                'image_url' => $imgeUrl,
                            ];
                            
                        }
                    }
                    $product['images'] = $images;
                break;
                case 'collection':
                    $categories = [];
                    if (isset($product[$serializeField]) && !empty($product[$serializeField])) {
                        $intermediateProductCategories = explode(';', $product[$serializeField]);
                        foreach ($intermediateProductCategories as $intermediateProductCategory) {
                            $collections = $this->getCategories(
                                $companyApplication,
                                [
                                    'name' => $intermediateProductCategory,
                                    'get_single_result' => 1
                                ]
                            );
                            $categories[] = !empty($collections) ? $collections->getCollectionId() : "";
                        }
                    }
                    $product['categories'] = $categories;
                break;
                // case 'brand':
                //     $brand_data = [];
                //     if(isset($product['brand']) && !empty($product['brand'])){
                //         $brands = $this->get_brands();
                //         $brands = isset($brands[0]) ? $brands[0] : $brands;
                //         foreach($brands as $brand){
                //             if( trim(strtolower($brand->name)) == trim(strtolower($product['brand'])) ) {
                //                 $product['brand_id'] = $brand->id;
                //             }
                //         }
                //     }
                // break;
            }
        }
       
        return $product;
    }

    public function arrangeUpdateCsvProduct($csvProduct)
    {
        //$csvProduct = array_filter($csvProduct);
        $notifications = [];
        $platform_product_data = []; // initialize all fields
        $requiredCsvFields = array(
            'name',
            'price',
            'fieldType',
        );
        $missingRequiredFields = [];
        //check for required Fields
        foreach ($requiredCsvFields as $requiredCsvField) {
            if (!isset($csvProduct[$requiredCsvField]) || empty($csvProduct[$requiredCsvField])) {
                $missingRequiredFields[] = $requiredCsvField;
            }
        }
        if (!empty($missingRequiredFields)) {
            $notifications['danger'][] = $this->container->get('translator')->trans(
                'message.product.update.required_fields_missing_%fields%',
                array(
                    'fields' => implode(',', $missingRequiredFields),
                )
            );

            return [$platform_product_data, $notifications];
        }
        
        $ignoreFields = array(
            'fieldType'
        );
        $stringFields = array(
            'sku',
            'description',
            'brand',
        );
        $integerFields = array(
            
        );
        $floatFields = array(
            'width',
            'price',
        );
        $bool_allowed = [
            'visible',
        ];
        // add all the form fields to prepare product data
        foreach ($csvProduct as $key => $data) {
            if (in_array($key, $ignoreFields)) {
                continue;
            }
            if (in_array($key, $stringFields) && empty($data)) {
                $data = '';
            } elseif (in_array($key, $bool_allowed)) {
                if ($data == 1 || strtolower($data) == 'true') {
                    $data = true;
                } else {
                    $data = false;
                }
            } elseif (in_array($key, $integerFields)) {
                if (empty($data) || !(int) $data) {
                    $data = 0;
                } else {
                    $data = (int) $data;
                }
            } elseif (in_array($key, $floatFields)) {
                if (empty($data) || !(float) $data) {
                    $data = 0.0;
                } else {
                    $data = (float) $data;
                }
            } elseif (empty($data)) {
                continue;
            }
            $platform_product_data[$key] = $data;
        }
        if (isset($csvProduct['images']) && !empty($csvProduct['images'])) {
            foreach ($csvProduct['images'] as $key => $imageFile) {
                if (isset($imageFile['image_url']) && !empty($imageFile['image_url'])) {
                    $data = @file_get_contents($imageFile['image_url']);
                    if ($data === false) {
                        unset($csvProduct['images'][$key]);
                    }
                } else {
                    unset($csvProduct['images'][$key]);
                }
            }
            $platform_product_data['images'] = $csvProduct['images'];
        }
        
        return [$platform_product_data, $notifications];
    }

    public function getCategories($companyApplication, $params = [])
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];

        $params = array_merge($default_params, $params);

        $collection_repo = $this->entityManager->getRepository(Collections::class);
        $collections = $collection_repo->getCollections($params, $companyApplication->getCompany());

        return $collections;
    }

    public function verify_seller_product_import($seller, $product, $platform_product_data)
    {
        if ($product != null) {
            if ($product->getSeller() != null) {
                if ($product->getSeller()->getId() == $seller->getId()) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function create_sample_export_product_csv($pattern, $options)
    {   
        $product_fields = $this->getProductFields(); 
        
        $include_fields = $product_fields;
        $export_array_data = [];
        // $response = $this->platformHelper->get_platform_product_list_v3(['page' => 1, 'limit' => 1]);
        // $export_array_data = $response['data']->data ?? [];
        $include_ids = [];
        $export_sorted_data = [];
        //$category_list = $this->getCategoryList(true);
        //$category_list = array_slice($category_list, 0, 2) ?? ['category-1'];
        //$categories = implode('///', $category_list);

        $sample_data = array(
            [
                'handleId' => 'product_f927e733-2a42-4671-b25e-d00e7a9de85b',
                'fieldType' => 'Product',
                'name' => 'Test Product 1',
                'description' => 'Product Description Test Description.',
                'collection' => 'collection1;collection2',
                'sku' => 'OFS',
                'price' => 400,
                'visible' => true,
                'weight' => 0.25,
                'brand' => 'Test Brand',
                'seller_id' => '2'
            ],
            [
                'handleId' => '',
                'fieldType' => 'Product',
                'name' => 'Test Product 2',
                'description' => 'Product Description Test Description.',
                'collection' => 'collection1;collection2',
                'sku' => 'OFS',
                'price' => 400,
                'visible' => true,
                'weight' => 0.25,
                'brand' => 'Test Brand',
                'seller_id' => ''
            ],
        );
        foreach ($sample_data as $sampleProduct) {
            $temp_dat = [
                'fieldType' => $sampleProduct['fieldType'],
                'handleId' => $sampleProduct['handleId'],
            ];
            foreach ($product_fields as $field_id => $field_data) {
                if ($field_id == 'id') {
                    continue;
                }
                if (isset($sampleProduct[$field_id])) {
                    $temp_dat[$field_id] = $sampleProduct[$field_id];
                }
            }
            $export_sorted_data[] = $temp_dat;
        }
        $importExportHelper = $this->getAppHelper('import_export');
        $importExportHelper->setCompanyApplication($this->companyApplication);
        
        return $importExportHelper->fn_export($pattern, $export_sorted_data, $options);
    }

    public function soft_delete_product($product_id, $companyApplication)
    {
        try {
            $storeProduct_repo = $this->entityManager->getRepository(Products::class);
            $productData = $storeProduct_repo->findOneBy(['_prod_id' => $product_id]);
            if (!empty($productData)) {
                
                $productData->setIsDeleted((int)1);
        
                $this->entityManager->persist($productData);
                $this->entityManager->flush();
            }
            
        } catch (DBALException $e) {
            $sql_error_code = $e->getPrevious()->getCode();
            if ($sql_error_code == '23000') {
                
                $this->logger->alert('******************** Webhook Error ********************');
                $this->logger->alert("Can not delete product already in use. ProdId: ". $product_id);

            } else {
                $this->logger->alert('******************** Webhook Error ********************');
                $this->logger->alert("Can not delete product. ProdId: ". $product_id);
            }
        }
    }

    public function updateMpCollections($data, $company)
    {   
        $notifications = [];
        foreach($data as $key => $value) {
            if(strlen((string)$value) > 2 ) {
                return;
            }
            $mp_category_repo = $this->entityManager->getRepository(Collections::class);
            $category = $mp_category_repo->findOneBy(['id' => $key, 'company' => $company]);
            if(!empty($category)) {
                $category->setComission( (float) $value);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            }
        }

        return $category;
    }

    public function getAllMpCollections($params)
    {
        $mp_category_repo = $this->entityManager->getRepository(Collections::class);
        
        $categories = $mp_category_repo->getCollections($params, $params['company']);

        return $categories;
    }

    public function getWixMpCategory($params)
    {
        $mp_category_repo = $this->entityManager->getRepository(Collections::class);
        
        $category = $mp_category_repo->findOneBy($params);

        return $category;
    }

    private function wineVarieties() {
        $wineVarieties = [
            'White Zinfand' => 'White Zinfand',
            'White merlot' => 'White merlot',
            'Chardonnay' => 'Chardonnay',
            'Pink Moscato' => 'Pink Moscato',
            'Grenache' => 'Grenache',
            'Cabernet Sauvignon' => 'Cabernet Sauvignon',
            'Sangiovese' => 'Sangiovese',
            'Pinot Noir' => 'Pinot Noir',
            'Bordeaux Blend' => 'Bordeaux Blend',
            'Blend' => 'Blend',
            'Meritage' => 'Meritage',
            'Chardonnay' => 'Chardonnay',
            'Sauvignon Blanc' => 'Sauvignon Blanc',
            'Riesling' => 'Riesling',
            'Cabernet Sauvignon' => 'Cabernet Sauvignon',
            'Merlot' => 'Merlot',
            'Pinot Noir' => 'Pinot Noir',
            'Syrah (Shiraz)' => 'Syrah (Shiraz)',
            'Malbec' => 'Malbec',
            'Tempranillo' => 'Tempranillo',
            'Zinfandel' => 'Zinfandel',
            'Sangiovese' => 'Sangiovese',
            'Grenache (Garnacha)' => 'Grenache (Garnacha)',
            'Nebbiolo' => 'Nebbiolo',
            'Chenin Blanc' => 'Chenin Blanc',
            'Gewürztraminer' => 'Gewürztraminer',
            'Pinot Grigio (Pinot Gris)' => 'Pinot Grigio (Pinot Gris)',
            'Cabernet Franc' => 'Cabernet Franc',
            'Petite Sirah (Durif)' => 'Petite Sirah (Durif)',
            'Barbera' => 'Barbera',
            'Semillon' => 'Semillon',
            'Muscat (Moscato)' => 'Muscat (Moscato)',
            'Grüner Veltliner' => 'Grüner Veltliner',
            'Carmenère' => 'Carmenère',
            'Vermentino' => 'Vermentino',
            'Viognier' => 'Viognier',
            'Albariño' => 'Albariño',
            'Montepulciano' => 'Montepulciano',
            'Primitivo' => 'Primitivo',
            'Nero d\'Avola' => 'Nero d\'Avola',
            'Carignan (Carignane)' => 'Carignan (Carignane)',
            'Touriga Nacional' => 'Touriga Nacional',
            'Grüner Silvaner' => 'Grüner Silvaner',
            'Aligoté' => 'Aligoté',
            'Mencía' => 'Mencía',
            'Mourvèdre (Monastrell)' => 'Mourvèdre (Monastrell)',
            'Muscadet (Melon de Bourgogne)' => 'Muscadet (Melon de Bourgogne)',
            'Roussanne' => 'Roussanne',
            'Marsanne' => 'Marsanne',
            'Glera' => 'Glera',
            'Tannat' => 'Tannat',
            'Aglianico' => 'Aglianico',
            'Pedro Ximénez' => 'Pedro Ximénez',
            'Grenache Blanc' => 'Grenache Blanc',
            'Tinta Roriz (Tempranillo)' => 'Tinta Roriz (Tempranillo)',
            'Verdelho' => 'Verdelho',
            'Viura (Macabeo)' => 'Viura (Macabeo)',
            'Cinsault' => 'Cinsault',
            'Gamay' => 'Gamay',
            'Petit Verdot' => 'Petit Verdot',
            'Petit Manseng' => 'Petit Manseng',
        ];
        asort($wineVarieties);
        $wineVarieties['Other'] =   'Other';
        return $wineVarieties;
    }

    private function bottleSize($data) {
        $bottle_sizes = [
            // bottle weight in kg.
            'Split (187.5ml)' =>  '0.3',
            'Half/Demi (375ml)' => '0.6',
            'Standard (750ml)' => '1.2',
            'Magnum (1.5L)' => '2.1',
            'Double Magnum/Jeroboam (3.0L)' => '4.8',
            'Rehoboam (4.5L)' => '7.3',
            'Imperial (6.0L)' => '11',
            'Salmanazar (9.0L)' => '14.5',
            'Balthazar (12.0L)' => '20',
            'Nebuchadnezzar (15.0L)' => '25',
            'Solomon (18.0L)' => '29',
            'Primat (27L)' => '40',
            'Midas (30L)' => '48',
        ];
        $value = '';
        foreach ($bottle_sizes as $key => $bottle_size) {
            if ($bottle_size == $data) {
                $value = $key;
            }
        }
        return $value;
    }
}