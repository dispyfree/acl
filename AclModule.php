<?php

class AclModule extends CWebModule
{

        /**
         * @var stategy
         * @desc The strategy to be used
         */
        public $strategy = "nestedSet.pathMaterialization";
        
        /**
         * @var stategy_config
         * @desc Configuration of strategies - for details about the configuration look at the specific strategy 
         *       (ex. components/strategies/nestedSet/pathMaterialization)
         */
        public $strategy_config = array();

        // getAssetsUrl()
        //    return the URL for this module's assets, performing the publish operation
        //    the first time, and caching the result for subsequent use.
        private $_assetsUrl;

        public function getAssetsUrl()
        {
            if ($this->_assetsUrl === null)
                $this->_assetsUrl = Yii::app()->getAssetManager()->publish(
                    Yii::getPathOfAlias('acl.assets') );
            return $this->_assetsUrl;
        }

        public function getPath($file)
        {
            $url = Yii::app()->getAssetManager()->publish(
            Yii::getPathOfAlias('application.modules.acl.assets.*'));
echo Yii::getPathOfAlias('application.modules.acl.assets.*'); die();
            $path = $url . '/' . $file;

            return $path;
        }
            
	public function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application

		// import the module-level models and components
		$this->setImport(array(
                        'acl.models.behaviors.*',
			'acl.models.*',
			'acl.components.*',
                        'acl.components.management.*'
		));
                Strategy::initialize();
	}

	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			// this method is called before any module controller action is performed
			// you may place customized code here
			return true;
		}
		else
			return false;
	}
}
