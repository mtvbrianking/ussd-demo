<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::any('/', [UssdController::class, '__invoke']);

Route::post('/old', [OldUssdController::class, '__invoke']);

$menus = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<menus>
<menu name="customer">
<options header="Choose gender" noback="no">
<!-- this is a comment -->
<option text="Male"><variable name="gender" value="M"/></option><option text="Female"><variable name="gender" value="F"/></option><option text="Not Stated"><variable name="gender" value=""/></option></options>
</menu>
</menus>
XML;

function getDomElements(\DOMNodeList $nodeList, ?string $nodeName): array
{
    return array_filter(iterator_to_array($nodeList), function($node) use($nodeName) {
        return $node instanceof \DOMElement &&
            ($nodeName && $node->nodeName == $nodeName);
    });
}

Route::any('/debug', function() use($menus) {
    $dom = new DomDocument();
    $dom->loadXML($menus);
    $xpath = new DomXPath($dom);

    $node = $xpath->query("/menus/menu/*[1]")->item(0);

    // $children = $xpath->query('option', $node);

    $children = $node->childNodes;

    /*foreach ($children as $idx => $child) {
        echo "<br/>Class: ".get_class($child);

        // echo "<br/>Type: {$child->nodeType}";

        if(! $child instanceof \DOMElement) {
            continue;
        }

        echo " <b>[{$child->nodeName} text=" . $child->attributes->getNamedItem("text")->nodeValue . "]</b>";
    }*/

    // dd(iterator_to_array($children));

    // $options = array_filter(iterator_to_array($children), function($child) use($class) {
    //     return $child instanceof \DOMElement && $child->nodeName == 'option';
    // });

    $options = getDomElements($children, 'option');

    dd($options);
});
