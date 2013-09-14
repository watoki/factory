<?php
namespace watoki\factory;

interface Provider {

    public function provide($class, array $args = array());

}