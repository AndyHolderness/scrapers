<?php

class XML
{
    public function readXMLtoArray()
    {
    
    }
    
    public function signArrayToXML($outputArray)
    {
        $textContents = '
<?xml version="1.0" encoding="UTF-8" ?>

<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/"
>
    <channel>
	<wp:wxr_version>1.2</wp:wxr_version>
	<wp:author></wp:author>
';
        foreach($outputArray as $output) {
            $page_title = str_replace('| Hideaways News', '', $output[3]);
            $page_contents = $output[5];
            $meta_title = $output[2];
            $textContents .= '
        <item>
            <title>'.$page_title.'</title>
            <description><![CDATA['.$meta_title.']]></description>
            <content:encoded><![CDATA['.$page_contents.']]></content:encoded>
            <excerpt:encoded><![CDATA[]]></excerpt:encoded>
            <wp:status>publish</wp:status>
            <wp:post_type>post</wp:post_type>
            <category domain="post_tag" nicename="redy"><![CDATA[Hideaways Blog]]></category>
            <category domain="category" nicename="news"><![CDATA[News]]></category>
        </item>
        ';
        }
        
        $textContents .= '
    </channel>
    </rss>
        ';
        file_put_contents('hideawaysXML.xml', $textContents);
        var_dump($textContents);
        
    }

}