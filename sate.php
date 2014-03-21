<?php

function echoTemplate($sPath, $aVars = [])
{
    echo getContents($sPath, $aVars);
}

function getContents($sPath, $aVars = [])
{
    $sTmpString = file_get_contents($sPath);

    $ext = pathinfo($sPath, PATHINFO_EXTENSION);

    switch(strtolower($ext))
    {
        case 'html':
            $sString = parseHtml($sTmpString, $aVars);
            break;
    }

    return $sString;
}

function parseHtml($sHtml, $aVars = [])
{
    $dom = new DOMDocument();
    $caller = new ErrorTrap([$dom, 'loadHTML']);
    $caller->call($sHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $nodes = getSateNodes($dom);

    foreach ($nodes as $node) {

        $src = getSource($node, $aVars);
        $sContent = getContents($src, $aVars);
        $iLoop = getLoop($node);

        $sTmp = $sContent;
        for($i = 0; $i < $iLoop; $i++)
        {
            $sContent .= $sTmp;
        }

        $childDoc = new DOMDocument();
        $caller = new ErrorTrap([$childDoc, 'loadHTML']);
        $caller->call('<div></div>'.$sContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        foreach($childDoc->documentElement->childNodes as $tempNode)
        {
            $impNode = $dom->importNode($tempNode, true);
            $node->parentNode->insertBefore($impNode, $node);
        }
        $node->parentNode->removeChild($node);
    }
    return $dom->saveHTML();
}

function getLoop(&$node)
{
    $iLoop = $node->getAttribute('loop');
    if(is_numeric($iLoop))
    {
        return $iLoop;
    }
    return 0;
}

function getSateNodes(&$dom)
{
    $finder = new DomXPath($dom);
    $classname = "sate";
    return $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
}

function getSource(&$node, &$aVars)
{
    $src = $node->getAttribute('src');
    $srcVar = preg_replace('(^%|%$)', '', $src);
    if(isset($aVars[$srcVar]))
    {
        $src = $aVars[$srcVar];
    }
    return $src;
}

class ErrorTrap {
    protected $callback;
    protected $errors = array();
    function __construct($callback) {
        $this->callback = $callback;
    }
    function call() {
        $result = null;
        set_error_handler(array($this, 'onError'));
        try {
            $result = call_user_func_array($this->callback, func_get_args());
        } catch (Exception $ex) {
            restore_error_handler();
            throw $ex;
        }
        restore_error_handler();
        return $result;
    }
    function onError($errno, $errstr, $errfile, $errline) {
        $this->errors[] = array($errno, $errstr, $errfile, $errline);
    }
    function ok() {
        return count($this->errors) === 0;
    }
    function errors() {
        return $this->errors;
    }
}
