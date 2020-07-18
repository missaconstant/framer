<?php

	namespace framer;

	/**
	*  Le modele principal !
	*/
	class modele
	{
		protected static $bd ;
		private $zone;

		public function __construct($zone, $fieldstoupate=[]){
			$this->zone 			= $zone;
			$this->fieldstoupate 	= $fieldstoupate;

			try {
				$pdo_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION ;
				modele::$bd = new \PDO(Config::$db_type . ':dbname=' . Config::$db_name . '; host=' . Config::$db_host, Config::$db_user, Config::$db_password, $pdo_options) ;
				modele::$bd->exec("set names utf8");
			}
			catch(\Exception $e)
			{
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
				unset( $vars['fieldstoupate'] );

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
	    public function update($blueprint=null, $fieldstoupate=null, $keytobind="id", $zone=null)
	    {
			// set zone
			$zone 			= $zone ?? $this->zone;
			$fieldstoupate 	= $fieldstoupate ?? $this->fieldstoupate;

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
					// because can't edit bound key
					if ($keytobind == $v) continue;

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
		* @method getJoins
		* Help joining
		*
		* Joining template
		*
		* $jointo = [
		*	"tablename" => [
		*		"alias" => "table1",
		*		"join" => [ "tabletojoin_column" => "tablejoinfrom_column" ],
		*		"take" => [ "tabletojoin_column1", "tabletojoin_column2", ... ]
		*		"type" => "LEFT | RIGHT"
		*	]
		* ];
		*/
		public function getJoins($zone=null, $jointo)
		{
			$zone = $zone ?? $this->zone;

			// set joins and takes
			$joins 	= [];
			$totake	= [];

			foreach ($jointo as $tablename => $joinning)
			{
				$alias 	= $joinning['alias'] ?? ( $tablename == $zone ? "_$zone" : $tablename );
				$join	= $joinning['join'];
				$takes	= $joinning['take'] ?? [];
				$type	= $joinning['type'] ?? 'LEFT';

				// create joins strings
				$jstr = [];

				foreach ( $join as $tto => $tfrom )
				{
					$jstr[] = "$alias.$tto = $zone.$tfrom";
				}

				$jstr = implode( " AND ", $jstr );

				// create takes string
				$__takes = [];

				foreach ( $takes as $k => $take )
				{
					$__takes[] = "$alias.$take as $alias". "_" ."$take";
				}

				$__takes = implode( ", ", $__takes );

				// compile join string
				$joins[] = "$type JOIN $tablename ". ( $tablename != $alias ? "AS $alias" : "" ) ." ON $jstr";

				// compile take string
				if ( strlen($__takes) ) $totake[] = $__takes;
				else $totake[] = $alias. ".*";
			}

			# final strings: joinings string and selects string
			$joins 		= implode( " ", $joins );
			$totake 	= count( $totake ) ? ( ", " . implode(",", $totake) ) : '';

			// echo $totake; exit();

			# return
			return (object) [ "joins" => $joins, "takes" => $totake ];
		}

		/**
	    * @method getSelf
	    */
		public function getSelf($id=null)
		{
			try {
				# try getting self datas
				$selfdatas = $this->getOne( $id );

				# stop the process of nothing found
				if ( !$selfdatas ) return;

				foreach ( $selfdatas as $k => $v )
				{
					$this->{ $k } = $v;
				}

				return true;
			}
			catch (\Exception $e) {
				return false;
			}
		}

	    /**
	    * @method getOne
	    */
	    public function getOne($id=null, $zone=null, $jointo=[], $searchon='id')
	    {
			try {
				// set zone and join
				$zone 	= $zone ?? $this->zone;
				$id		= $id ?? $this->id;
				$joins 	= $this->getJoins( $zone, $jointo );
				$_join	= $joins->joins;
				$_take	= $joins->takes;

				// echo "SELECT $zone.* $_take FROM $zone $_join WHERE $zone.id=$id";
				// exit();

				// do query
		        $q = self::$bd->query("SELECT $zone.* $_take FROM $zone $_join WHERE $zone.$searchon=$id");
		        $r = $q->fetchAll( \PDO::FETCH_OBJ );

		        return $r[0] ?? false;
			}
			catch (\Exception $e) {
				return false;
			}
	    }

	    /**
	    * @method getAll
	    */
	    public function getAll($zone=null, $jointo=[], $wheres=[], $limit=null, $order=null)
	    {
			try {
				// set zone and joins
				$zone 	= $zone ?? $this->zone;
				$joins 	= $this->getJoins( $zone, $jointo );
				$_join	= $joins->joins;
				$_take	= $joins->takes;

				// set wheres
				$wlist = [];

				foreach ( $wheres as $column => $value )
				{
					$wlist[] = "$zone.$column='$value'";
				}

				// set limit & order
				$limit = $limit ? "LIMIT " . ( is_array($limit) ? implode( ', ', $limit ) : $limit ) : '';
				$order = $order && count($order) == 2 ? "ORDER BY " . $order[0] . " " . $order[1] : "";

				// echo "SELECT DISTINCT $zone.* $_take FROM $zone $_join WHERE " . (count($wlist) ? implode(' AND ', $wlist) : '1') . " $order $limit";
				// exit();

				$q = self::$bd->query("SELECT DISTINCT $zone.* $_take FROM $zone $_join WHERE " . (count($wlist) ? implode(' AND ', $wlist) : '1') . " $order $limit");
				$r = $q->fetchAll( \PDO::FETCH_OBJ );

				return $r;
			}
			catch (\Exception $e) {
				// die ( $e->getMessage() );
	            return false;
			}
	    }

		/**
	    * @method search
	    */
		public function search($zone=null, $wheres=[])
		{
			try {
				// set zone
				$zone = $zone ?? $this->zone;

				// set searches wheres
				$list = [];

				foreach ( $wheres as $column => $value )
				{
					$list[] = "$column='$value'";
				}

				// do search
				$q = self::$bd->query("SELECT * FROM $zone WHERE " . implode(" AND ", $list));
				$r = $q->fetchAll( \PDO::FETCH_OBJ );

				return count( $r ) ? $r : false;
			}
			catch (\Exception $e) {
				return false;
			}
		}

		/**
	    * @method toggle
	    */
		public function toggle($zone=null, $column='active', $wherestring=null)
		{
			try {
				$zone 	= $zone ?? $this->zone;
				$id		= $this->id;
				$news	= abs($this->{ $column } - 1); // new state

				# execute
				$q = self::$bd->exec("UPDATE $zone SET $column=$news WHERE " . ( $wherestring ?? "id=$id" ));

				return $q;
			}
			catch (\Exception $e) {
				return false;
			}
		}

		/**
		* @method count
		*/
		public function count($zone=null, $wherestring=false)
		{
			try {
				$zone 	= $zone ?? $this->zone;

				# execute
				$q = self::$bd->query("SELECT count(*) as counts FROM $zone " . ( $wherestring ? "WHERE $wherestring" : "" ));
				$r = $q->fetchAll( \PDO::FETCH_OBJ );

				return $r[0]->counts;
			}
			catch (\Exception $e) {
				return false;
			}
		}

	    /**
	    * @method remove
	    */
	    public function remove($zone=null, $id=null, $key='id')
	    {
			try {
				// set zone
				$zone 	= $zone ?? $this->zone;
				$id		= $id ? $id : $this->id;

		        $q = self::$bd->exec("DELETE FROM $zone WHERE $key=$id");
		        return $q;
			}
			catch (\Exception $e) {
				return false;
			}
	    }
	}
