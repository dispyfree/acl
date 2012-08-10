<?php

class m120806_161237_initialization extends CDbMigration
{
    
	public function up()
	{
            $this->createActionTable();
            $this->fillActionTable();
            $this->createPermissionTable();
            
            $types = array('aro', 'aco');
            foreach($types as $type)
                $this->createTypeTables($type);
            
            return true;
	}
        
        protected function createActionTable(){
            $this->createTable('{{action}}', array(
                'id'        => 'pk',
                'name'      => 'string',
                'created'   => 'integer'
            ));
            $this->createIndex('name', '{{action}}', 'name', true);
        }
        
        protected function fillActionTable(){
            $shippedActions = array(
                'create', 'read', 'update', 'delete', 'grant', 'deny'
            );
            foreach($shippedActions as $action)
                $this->insert('{{action}}', array(
                    'name'      => $action,
                    'created'   => time()
                ));
        }
        
        protected function createPermissionTable(){
            $this->createTable('{{permission}}', array(
                'id'        => 'pk',
                'aco_id'    => 'integer',
                'aro_id'    => 'integer',
                'aco_path'  => 'text',
                'aro_path'  => 'text',
                'action_id' => 'integer',
                'created'   => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            ));
            
            $this->createIndex('most_common_search', '{{permission}}', 
                    'aco_path(20), aro_path(20), action_id');
            $this->createIndex('created', '{{permission}}', 'created');
        }
        
        protected function createTypeTables($type){
            
            //First the nodes
            $this->createNodeTable($type);
            $this->createCollectionTable($type);
           
        }
        
        protected function createNodeTable($type){
             $table = '{{'.$type.'}}';
             
            $this->createTable($table, array(
                'id'     => 'pk',
                'collection_id' => 'integer',
                'path'  => 'text'
            ));
            
            $this->createIndex('collection_id', $table, 'collection_id');
            $this->createIndex('path', $table, 'path(10)');
        }
        
        protected function createCollectionTable($type){
            $table = '{{'.$type.'_collection}}';
            
            $this->createTable($table, array(
                'id'        => 'pk',
                'alias'     => 'string',
                'model'     => 'string',
                'foreign_key'   => 'integer',
                'created'   => 'integer'
            ));
            $this->createIndex('alias', $table, 'alias(10)', true);
            $this->createIndex('mapping', $table, 'model, foreign_key', true);
        }
        

	public function down()
	{
		//Simply remove the tables
                $tables = array('action', 'permission', 'aro', 'aro_collection',
                    'aco', 'aco_collection');
                
                foreach($tables as $table)
                    $this->dropTable('{{'.$table.'}}');
                
		return true;
	}
}