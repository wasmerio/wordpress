<?php
/**
 * Does a magic login to the WordPress site using the Wasmer API and a one-time token.
 *
 * @package WordPress-Wasmer
 */

$_GET["action"] = "magiclogin";

include_once(dirname(__FILE__) . '/wasmer.php');
