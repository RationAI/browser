<?php
//hack to access data on different origin
echo file_get_contents(urldecode($_GET["proxy"]));

