<?php

if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/../.env')) {
    return;
}

$dotenv = \Dotenv\Dotenv::createImmutable(realpath($_SERVER['DOCUMENT_ROOT'] . '/..'))
    ->load();
