<?php

function echoTemplate($sPath)
{
    echo getContents($sPath);
}

function getContents($sPath)
{
    $sTmpString = file_get_contents($sPath);

    $ext = pathinfo($sPath, PATHINFO_EXTENSION);

    switch(strtolower($ext))
    {
        case 'html':
            $sString = parseHtml($sTmpString);
            break;
    }

    return $sString;
}

function parseHtml($sHtml)
{
    static $bRoot = true;
    $bRootloc = $bRoot;
    $bRoot = false;

    $dom = new DOMDocument();
    $caller = new ErrorTrap([$dom, 'loadHTML']);

    if($bRootloc)
    {
        $caller->call($sHtml);
    }
    else
    {
        $caller->call($sHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    }

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

            $sContent = "<div id='__sate_internal__'>$sContent</div>";
            $caller->call($sContent, LIBXML_HTML_NODEFDTD);
        }
        catch(\Exception $e)
        {
            echo $e;
        }

        $nodesContent = $childDoc->getElementById('__sate_internal__');
        $ins = $node->parentNode;
        foreach($nodesContent->childNodes as $nodeContent)
        {
            $importNode = $dom->importNode($nodeContent, true);
            $ins->insertBefore($importNode, $node);
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
