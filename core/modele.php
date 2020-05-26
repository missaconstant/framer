<?php

	namespace framer;

	/**
	*  Le modele principal !
	*/
	class modele
	{
		protected static $bd ;
		private $zone;

		public function __construct($zone){
			$this->zone = $zone;

			try{
				$pdo_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION ;
				modele::$bd = new \PDO(Config::$db_type . ':dbname=' . Config::$db_name . '; host=' . Config::$db_host, Config::$db_user, Config::$db_password, $pdo_options) ;
				modele::$bd->exec("set names utf8");
			}
			catch(\Exception $e){
				modele::$bd = false;
				die('Erreur : '.$e->getMessage()) ;
			}
		}

		/**
		* @method getByFields
		*/
		public function getByFields( $tablename, $fields )
	    {
	        try {
	            // creating query string
	            $qstring = [];

	            foreach ( $fields as $field => $value )
	            {
	                $qstring[] = "$field=:$field";
	            }

	            // do query
	            $q = self::$bd->prepare( "SELECT * FROM $tablename WHERE " . implode(' AND ', $qstring) );
	            $q->execute( $fields );

	            return $q->fetchAll(\PDO::FETCH_OBJ);
	        }
	        catch (\Exception $e) {
	            // die ( $e->getMessage() );
	            return false;
	        }
	    }

		/**
	    * @method create
	    */
	    public function create($blueprint=null, $zone=null)
	    {
			// set zone
			$zone = $zone ?? $this->zone;

	        try {
	            // get modele values
	            $vars = get_object_vars( $blueprint ?? $this );

	            // unset id & zone
	            unset( $vars['id'] );
				unset( $vars['zone'] );

	            // mount query
	            $query  = '';
	            $left   = [];
	            $right  = [];

	            foreach ($vars as $k => $v)
	            {
	                if ($k == 'id') continue;

	                $left[]     = $k;
	                $right[]    = ":$k";
	            }

	            // do query
	            $q = self::$bd->prepare( "INSERT INTO $zone( ". implode(', ', $left) ." ) VALUES ( ". implode(', ', $right) ." )" );
	            $r = $q->execute($vars);

	            return self::$bd->lastInsertId();
	        }
	        catch (\Exception $e) {
	            die ( $e->getMessage() );
	            return false;
	        }
	    }

	    /**
	    * @method update
	    */
	    public function update($blueprint, $fieldstoupate, $keytobind="id", $zone=null)
	    {
			// set zone
			$zone = $zone ?? $this->zone;

	        try {
	            // get modele values
	            $vars = get_object_vars( $blueprint ?? $this );
				$_var = [];

				// zone not necessary
				unset( $vars['zone'] );

	            // mount query
	            $query  = '';
	            $left   = [];

	            foreach ($fieldstoupate as $k => $v)
	            {
					// query array
	                $left[] = "$v=:$v";

					// to ensure that only required fields will be passed to execute
					$_var[ $v ] = $vars[ $v ];
	            }

				// complete the key
				$_var[ $keytobind ] = $vars[ $keytobind ];

	            // do query
	            $q = self::$bd->prepare( "UPDATE $zone SET ". implode(',', $left) ." WHERE $keytobind=:$keytobind" );
	            $r = $q->execute($_var);

	            return true;
	        }
	        catch (\Exception $e) {
	            // die ( $e->getMessage() );
	            return false;
	        }
	    }

	    /**
	    * @method getOne
	    */
	    public function getOne($id, $zone=null)
	    {
			// set zone
			$zone = $zone ?? $this->zone;

	        $q = self::$bd->query("SELECT * FROM $zone WHERE id=$id");
	        $r = $q->fetchAll( \PDO::FETCH_OBJ );

	        return $r[0] ?? false;
	    }

	    /**
	    * @method getAll
	    */
	    public function getAll($zone=null)
	    {
			try {
				// set zone
				$zone = $zone ?? $this->zone;

				$q = self::$bd->query("SELECT * FROM $zone");
				$r = $q->fetchAll( \PDO::FETCH_OBJ );

				return $r;
			}
			catch (\Exception $e) {
				// die ( $e->getMessage() );
	            return false;
			}
	    }

	    /**
	    * @method remove
	    */
	    public function remove($id, $zone=null)
	    {
			// set zone
			$zone = $zone ?? $this->zone;

	        $q = self::$bd->exec("DELETE FROM $zone WHERE id=$id");
	        return true;
	    }
	}
