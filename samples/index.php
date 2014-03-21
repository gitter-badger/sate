<?php

require_once('sate.php');

sate\echoTemplate('test.html', [
   'ahhh' => 'test2.html',
   'embeddedVar' => 'test4.html'
]);
