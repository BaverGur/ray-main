<?php

namespace RAY\Amazon;

use Symfony\Component\DomCrawler\Crawler;

class ProductParser
{
    protected Crawler $crawler;
    protected AmazonProduct $amazonProduct;

    protected function __construct($html)
    {
        $this->crawler = new Crawler($html);
        $this->amazonProduct = new AmazonProduct();
    }

    /**
     * @param $html
     * @return AmazonProduct
     */
    public static function parseByHtml($html): AmazonProduct
    {
        return (new ProductParser($html))->parse();
    }

    public function parse(): AmazonProduct
    {
        $this->amazonProduct->asin = $this->crawler->filter('#ASIN')->first()->attr('value');
        $this->amazonProduct->title = $this->crawler->filter('#productTitle')->first()->text();
        $this->amazonProduct->price = floatval(preg_replace('/[^0-9.]/', '', $this->crawler->filter('#price_inside_buybox')->first()->text()));
        $this->amazonProduct->shippingFee = floatval(preg_replace('/[^0-9.]/', '', $this->crawler->filter('#price-shipping-message span')->first()->text()));
        
         $dimensionsAll = $this->crawler->filter('#detailBullets_feature_div ul')->first();
        
        if ($dimensionsAll->count() > 0){
            $dimensionsText = $this->crawler->filter('#detailBullets_feature_div ul')->first()->text();
        }
        else{
           
            $dimensionsAll = $this->crawler->filter('#productDetails_techSpec_section_1')->first();
            if ($dimensionsAll->count() > 0){
                
                $dimensionsText = $dimensionsAll->text();

            }
        }



        if ($dimensionsAll->count() > 0){
            
               
           
           
            if (strpos($dimensionsText, ';') !== false) {
                
                if(str_contains($dimensionsText, ' g ')){
                   
                    $dimensions = explode(' g ',$dimensionsText)[0];
                   
                    $dimensions =  substr($dimensions, strrpos( $dimensions, ':') );
                    if(str_contains($dimensions, ';')){

                    
                        $weight = floatval(preg_replace('/[^0-9.]/', '', explode(';',$dimensions)[1])) / 1000;
                        
                        $depth_clear=trim(explode('x',$dimensions)[0]);
                        $depth_clear =substr($depth_clear, strrpos( $depth_clear, ' ') );
                        $package_depth = floatval(preg_replace('/[^0-9.]/', '', $depth_clear));
                        $package_width = floatval(preg_replace('/[^0-9.]/', '', explode('x',$dimensions)[1]));
                        $package_height = floatval(preg_replace('/[^0-9.]/', '', explode(';',explode('x',$dimensions)[2])[0]));
                        
                        $this->amazonProduct->package_width =$package_width;
                        $this->amazonProduct->package_height = $package_height;
                        $this->amazonProduct->package_depth = $package_depth;
                        $this->amazonProduct->weight = $weight;
                        }
                }
                else if(str_contains($dimensionsText, ' Kg ')){
                   
                    $dimensions = explode(' Kg ',$dimensionsText)[0];
                   
                    $dimensions =  substr($dimensions, strrpos( $dimensions, ':') );
                    if(str_contains($dimensions, ';')){

                    
                        $weight = floatval(preg_replace('/[^0-9.]/', '', explode(';',$dimensions)[1]));
                        
                        $depth_clear=trim(explode('x',$dimensions)[0]);
                        $depth_clear =substr($depth_clear, strrpos( $depth_clear, ' ') );
                        $package_depth = floatval(preg_replace('/[^0-9.]/', '', $depth_clear));
                        $package_width = floatval(preg_replace('/[^0-9.]/', '', explode('x',$dimensions)[1]));
                        $package_height = floatval(preg_replace('/[^0-9.]/', '', explode(';',explode('x',$dimensions)[2])[0]));
                        
                        $this->amazonProduct->package_width =$package_width;
                        $this->amazonProduct->package_height = $package_height;
                        $this->amazonProduct->package_depth = $package_depth;
                        $this->amazonProduct->weight = $weight;
                     }
                }
            }
        }


        
       
        return $this->amazonProduct;
    }
}
