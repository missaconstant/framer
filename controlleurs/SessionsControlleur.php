<?php

	namespace framer;


	class SessionsControlleur extends controlleur
	{

		/**
		* @method login
		* user login action
		*/
		public function login($login, $password, $tab="utilisateurs", $key1="login", $key2="motdepasse")
		{
			# get potential user from datas
			$m = $this->loadModele( $tab );
			$u = $m->search(null, [ $key1 => $login, $key2 => $password ]);

			if ( $u )
			{
				$m->id = $u[0]->id;
				$m->getSelf();

				# then create seassion hash and save it
				$m->sessionhash = hash( 'sha256', $login . $password . $_SERVER['REMOTE_ADDR'] . date('dmY') );

				# then save all this
				if ( $m->update() )
				{
					return $m->sessionhash;
				}
			}
			else {
				return false;
			}
		}


		/**
		* @method isUser
		* check wether user is logged in
		*/
		public function isUser($session_hash, $tab="utilisateurs")
		{
			$m = $this->loadModele( $tab );
			$u = $m->search(null, [ "sessionhash" => $session_hash ]);

			# if session hash exists, check its integrity
			if ( $u )
			{
				$m->id = $u[0]->id;
				$m->getSelf();

				# compute hash from remote address and credentials
				# to be sure that session hash had not been shared
				$computed_hash = hash( 'sha256', $m->login . $m->motdepasse . $_SERVER['REMOTE_ADDR'] . date('dmY') );

				if ( $computed_hash === $m->sessionhash )
				{
					return $u[0];
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		}
	}
