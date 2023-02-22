<?php

namespace App\Financial;

class BagNode {
    public $info = array();
    public $heuristic = 0;
    public $steps = 0;
    public $transfer = NULL;
    public $prev = NULL;

    public function defineValues($info, $heuristic, $steps){
        $this->info = $info;
        $this->heuristic = $heuristic;
        $this->steps = $steps;
    }

    public function getInfo(){
        return $this->info;
    }

    public function getHeuristic(){
        return $this->heuristic;
    }

    public function getSteps(){
        return $this->steps;
    }

    public function getTransfer(){
        return $this->transfer;
    }

    public function setTransfer($transfer){
        $this->transfer = $transfer;
    }

    public function getPrev(){
        return $this->prev;
    }

    public function setPrev($prev){
        $this->prev = $prev;
    }
}