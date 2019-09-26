<?php

	namespace framer;

	/**
	*  Le controlleur principal ! Le boss !
	*/
	class controlleur
	{
		/**
		* Return correct view to user
		* @params String $path
		* @params Array $vars
		* @return void
        * @throws \Exception view
		*/
		function render($path, $vars=false){
			if($vars!=false) extract($vars) ;
			if(preg_match("#[a-zA-Z0-9]+/[a-zA-Z0-9]+#", $path)){
				$path = explode('/', $path) ;
				$ch = $path[0] ;
				$vu = $path[1] ;
				if(file_exists(Statics::$ROOT.'vues/'.$ch.'/'.$vu.'.php'))
					include_once Statics::$ROOT.'vues/'.$ch.'/'.$vu.'.php' ;
				else
					throw new \Exception("La vue demandée est introuvable.", 1) ;
			}
			else if(preg_match("#[a-zA-Z0-9]+#", $path)){
				if(file_exists(Statics::$ROOT.'vues/'.$path.'.php'))
					include_once Statics::$ROOT.'vues/'.$path.'.php' ;
				else
					throw new \Exception("La vue ".$path." demandée est introuvable.", 1) ;
			}
		}

		function redirToOld($path,$from='window'){
			if($from=='frame') echo'<script>window.top.window.location.href="' . BASE_URI . $path.'"</script>' ;
			else header("location: ". BASE_URI . $path) ;
		}

		function redirTo($path,$from='window'){
			if($from=='frame') echo'<script>window.top.window.location.href="' . $path. '"</script>' ;
			else header("location: " . $path) ;
		}

		function loadModele($modele=false){
			if(!$modele){
				$model = get_class($this) ;
				$model = explode('Controlleur', $model) ;
				include_once Statics::$ROOT.'modeles/'.ucfirst($model[0]).'Modele.php' ;
				$model = "\\framer\\" . ucfirst($model[0]) . "Modele";

				return new $model() ;
			}
			else{
				include_once Statics::$ROOT.'modeles/'.ucfirst($modele).'Modele.php' ;
				$modele = "\\framer\\" . ucfirst($modele) . "Modele";
				return new $modele() ;
			}
		}

		function loadClass($classe=false){
			if(!$classe){
				$class = get_class($this) ;
				$class = explode('Controlleur', $class) ;
				$class = ucfirst($class[0]) ;
				include_once Statics::$ROOT.'classes/'.$class.'.php' ;
				return new $class() ;
			}
			else{
				include_once Statics::$ROOT.'classes/'.$classe.'.php' ;
				$class = ucfirst($classe) ;
				return new $class() ;	
			}
		}

		function loadController($name){
			include_once Statics::$ROOT.'controlleurs/'.ucfirst($name).'Controlleur.php';
			$controlleur = ucfirst($name).'Controlleur' ;
			return new $controlleur() ;
		}

		function jsonize($array)
		{
			return json_encode($array);
		}

		function json_answer($array)
		{
			echo $this->jsonize($array);
		}

		function json_error($message, $morevalue = [])
        {
            $this->json_answer(array_merge(["error" => true, "message" => $message], $morevalue));
        }

		function json_success($message, $morevalue = [])
        {
            $this->json_answer(array_merge(["error" => false, "message" => $message], $morevalue));
        }

	}