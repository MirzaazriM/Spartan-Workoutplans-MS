<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 7/16/18
 * Time: 11:25 AM
 */

namespace Component;


class LinksConfiguration
{

    private $config = 'LOCAL';
    private $localTagsUrl = 'http://spartan-tags:8888';
    private $localWorkoutsUrl = 'http://spartan-workouts:8888';
    private $onlineTagsUrl = '12.456.43.54';
    private $onlineWorkoutsUrl = '467.21.980.046';

    public function __construct()
    {
    }

    public function getUrls():array {

        if($this->config == 'LOCAL'){
            return [
                $this->localTagsUrl,
                $this->localWorkoutsUrl
            ];
        }else {
            return [
                $this->onlineTagsUrl,
                $this->onlineWorkoutsUrl
            ];
        }
    }
}