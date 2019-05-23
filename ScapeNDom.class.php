<?php class ScapeNDom
{

    protected $pages = [];
    protected $redirects = [];
    protected $currentpage = "";

    public function singlePage($page = 'https://google.com'){

        $this->currentpage = $page;
        $this->pages[$this->currentpage] = [];
        $this->scrape($this->currentpage);

    }
    
    public function multiplePage(Array $arrayOfPages = []){
        foreach($arrayOfPages as $pageItem){
            echo "Scraping- ".$pageItem[0].PHP_EOL;
            $this->singlePage($pageItem[0]);
        }
    }

    public function getPages(){
        return $this->pages;
    }

    private function scrape($url){

        $rawscrape = file_get_contents($url);

        $this->pages[$this->currentpage]['raw'] = $rawscrape;
        $this->pages[$this->currentpage]['dom'] = [];

        $pageHTMLDom = new DOMDocument( "1.0", "ISO-8859-15" );

        @$pageHTMLDom->loadHTML($rawscrape);
        $this->stepThrough($pageHTMLDom->childNodes, $this->pages[$this->currentpage]['dom']);
    }

    public function stepThrough(DOMNodeList $element, Array &$pageDomLocation){

        foreach($element as $subelement){

            if(get_class($subelement) == "DOMElement"){


                if(isset($pageDomLocation[$subelement->tagName]) && ($currentElementIndex = sizeof($pageDomLocation[$subelement->tagName]))){
                    $pageDomLocation[$subelement->tagName][$currentElementIndex] = [];
                }else{
                    $currentElementIndex = 0;
                    $pageDomLocation[$subelement->tagName][0] = [];
                }

                $atribs = $this->parseAttributes($subelement->attributes);

                if($atribs){
                    $pageDomLocation[$subelement->tagName][$currentElementIndex]['attributes'] = $atribs;
                }
                
                if($subelement->textContent){
                    $pageDomLocation[$subelement->tagName][$currentElementIndex]['text'] = $subelement->textContent;
                }

                $pageDomLocation[$subelement->tagName][$currentElementIndex]['html'] = $this->getInnerHTML($subelement);

                $pageDomLocation[$subelement->tagName][$currentElementIndex]['children'] = [];
                $this->stepThrough($subelement->childNodes, $pageDomLocation[$subelement->tagName][$currentElementIndex]['children']);

                if(!$pageDomLocation[$subelement->tagName][$currentElementIndex]['children']){
                    unset($pageDomLocation[$subelement->tagName][$currentElementIndex]['children']);
                }
            }
        }
        
    }


    public function parseAttributes($attributes){

        $attrs = [];

        foreach($attributes as $attribute){
            /**@var DOMAttr $attribute*/
            $attrs[$attribute->nodeName] = $attribute->nodeValue;
        }

        return $attrs;
    }

    public function getTagByAttribute($page, $tagName, $attributeName = null, $value = null){
        $results = [];
        $this->traverseAndSearch($this->pages[$page]['dom'], $tagName, $results, $attributeName, $value);

        return $results;
    }

    public function traverseAndSearch($dom, $tagName, &$resultSet = [], $attributeName = null, $value = null){


        foreach($dom as $tag => $tagContent) {
            foreach ($tagContent as $tagInstance) {
                if ($tag == $tagName) {
                    if ($attributeName && $value) {
                        if (isset($tagInstance['attributes']) && key_exists($attributeName, $tagInstance['attributes']) && stristr($tagInstance['attributes'][$attributeName], $value)) {
                            $resultSet[] = $tagInstance;
                        }
                    } else {
                        $resultSet[] = $tagInstance;
                    }
                }

                if (isset($tagInstance['children'])) {
                    $this->traverseAndSearch($tagInstance['children'], $tagName, $resultSet, $attributeName, $value);
                }
            }
        }
        
        return $resultSet;
    }

    public function getInnerHTML($node)
    {
        $innerHTML= '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML( $child );
        }

        return $innerHTML;
    }

    public function downloadImage($url, $imageURL){

        if(!sizeof($imageURL)){
            return false;
        }

        if($imageURL[0] == "/"){
            $imageURL = substr($imageURL,1);
        }

        if(file_exists("images/".$imageURL)){
            return "images/".$imageURL;
        }

        $fileSplit = explode("/",$imageURL);
        $imageName = array_pop($fileSplit);

        $currentPath = "images/";

        foreach($fileSplit as $folder){
            $currentPath .= $folder;
            @mkdir($currentPath);

            $currentPath .= "/";
        }

        file_put_contents($currentPath.$imageName, file_get_contents($url.$imageURL));

        return $currentPath.$imageName;
    }
    
    public function getTagsByAttributes($page, $tagName, $tagName2, $attributeName = null, $value = null){
        $results = [];
        $results[] = $this->traverseAndSearch($this->pages[$page]['dom'], $tagName, $results, $attributeName, $value);
        $results[] +=  $this->traverseAndSearch($this->pages[$page]['dom'], $tagName2, $results, $attributeName, $value);
        
        return $results;
    }

}