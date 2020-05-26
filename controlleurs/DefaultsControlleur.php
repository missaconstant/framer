<?php

	namespace framer;

	/**
	*
	*/
	class DefaultsControlleur extends controlleur
	{
		private $mdl;

		public function __construct()
		{

		}

		public function index()
		{
			$this->render('index');
		}
	}
