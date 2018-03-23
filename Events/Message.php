<?php

namespace Qubit\Bundle\QubitMqBundle\Events;

/**
 * Message
 * @package Qubit\Bundle\QubitMqBundle\Events\Message
 */
class Message
{
    const ENTRY_TYPE = 'EVENT';
    const LOG_LEVEL = 'INFO';
    
    private $time;
    private $tracking;
    private $entryType;
    private $loglevel;
    private $module;
    protected $component = '';
    protected $action = '';
    private $tags;
    private $payload = [];
    
    /**
     * __construct
     */
    public function __construct()
    {
        $this->entryType = self::ENTRY_TYPE;
        $this->loglevel = self::LOG_LEVEL;
    }
    
    /**
     * setTracking
     *
     * @param string $tracking
     */
    public function setTracking($tracking)
    {
        $this->tracking = $tracking;
    }

    /**
     * setModule
     *
     * @param string $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }
    
    /**
     * setTags
     *
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }
    
    /**
     * setPayload
     *
     * @param array $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }
    
    /**
     * getTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * getTracking
     */
    public function getTracking()
    {
        return $this->tracking;
    }

    /**
     * getEntryType
     */
    public function getEntryType()
    {
        return $this->entryType;
    }

    /**
     * getLoglevel
     */
    public function getLoglevel()
    {
        return $this->loglevel;
    }

    /**
     * getModule
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * getComponent
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * getAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * getTags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * getPayload
     */
    public function getPayload()
    {
        return $this->payload;
    }
    
    /**
     * getRoutingKey
     */
    public function getRoutingKey()
    {
        return $this->module . '.' . $this->component . '.' . $this->action;
    }
    
    /**
     * doValidations
     */
    public function doValidations()
    {
        if (empty($this->payload) || empty($this->module) || empty($this->component) || empty($this->action)) {
            return false;
        }
        return true;
    }
    
    /**
     * serialize
     *
     * @return array
     */
    public function serialize()
    {
        $dateTime = new \DateTime();
        $dateString = $dateTime->format('c');
        
        $this->time = $dateString;
        
        return array(
            'time' => $this->time,
            'tracking' => $this->tracking,
            'entry_type' => $this->entryType,
            'loglevel' => $this->loglevel,
            'module' => $this->module,
            'component' => $this->component,
            'action' => $this->action,
            'tags' => $this->tags,
            'payload' => (array)$this->payload
        );
    }
    
    /**
     * deserialize
     *
     * @param array $elements
     */
    public function deserialize(array $elements)
    {
        $this->time = $elements['time'];
        $this->tracking = $elements['tracking'];
        $this->entryType = $elements['entry_type'];
        $this->loglevel = $elements['loglevel'];
        $this->module = $elements['module'];
        $this->component = $elements['component'];
        $this->action = $elements['action'];
        $this->tags = $elements['tags'];
        $this->payload = (object)$elements['payload'];
    }
}
