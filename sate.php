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

function parseHtml($sHtml, $aVars)
{
    $dom = new DOMDocument();
    $caller = new ErrorTrap([$dom, 'loadHTML']);

    $caller->call($sHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $finder = new DomXPath($dom);
    $classname = "sate";
    $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
    foreach ($nodes as $node) {
        $src = $node->getAttribute('src');
        $sContent = getContents($src);

        $childDoc = NULL;
        try
        {
            $childDoc = new DOMDocument();
            $caller = new ErrorTrap([$childDoc, 'loadHTML']);

            $caller->call('<div></div>'.$sContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        catch(\Exception $e)
        {
            echo $e;
        }

        foreach($childDoc->documentElement->childNodes as $tempNode)
        {
            $impNode = $dom->importNode($tempNode, true);
            $node->parentNode->insertBefore($impNode, $node);
        }
        $node->parentNode->removeChild($node);
    }
    return $dom->saveHTML();
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
