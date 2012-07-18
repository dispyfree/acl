<?php
/**
 * This class simply exists to get a more or less "good-style" way of hiding
 * the actual used class. 
 * Downside: actually no parameters are accepted for the constructor :)
 * The rest should be quite self-explanatory.
 * 
 * All calls to this class will be redirected to the class pointed by $class
 * @package acl.base
 * @author dispy<dispyfree@googlemail.com>
 * @license LGPLv2
 */
class HiddenClass{
    
    protected $class = NULL;
    protected $obj   = NULL;
    
    public function __construct($className, $originalArguments){
        $this->class = $className;
        $this->instantiateClass($originalArguments);
    }
    
    public function instantiateClass($arg){
        
        switch(count($arg)){
            case 0:
                $this->obj = new $this->class();
                break;
            case 1:
                $this->obj = new $this->class($arg[0]);
                break;
            case 2:
                $this->obj = new $this->class($arg[1]);
                break;
            case 3:
                $this->obj = new $this->class($arg[2]);
                break;
            default:
                throw new RuntimeException('Please expand this function yourself :)');
                break;
        }
    }
    
    public function pretends(){
        return $this->class;
    }
    
    public function __call($funcName, $args){
        return call_user_func_array(array($this->obj, $funcName), $args);
    }
    
    public function __get($name) {
        return $this->obj->{$name};
    }
    
    public function __set($key, $val){
        return $this->obj->{$key} = $val;
    }
    
    public function __isset($key){
        return isset($this->obj->{$key});
    }
    
    public function __unset($key){
        return $this->obj->{$key};
    }

}

?>
