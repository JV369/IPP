<?php
	/**
	* Projekt IPP - parse.php - syntakticka, sematicka analyza, vytvoreni xml
	* Jmeno: Jan Vavra
	* Login: xvavra20
	*/


	/**
	* @brief trida na zpracovani radku
	*/
	class Row
	{
		public $input;
		protected $int_reg;
		protected $bool_reg;
		protected $comment_reg;
		protected $var_reg;
		protected $string_reg;
		protected $label_reg;
		protected $type_reg;
		protected $symb_reg; 
		protected $check;

		//init tridy - pripravi si regexy pro porovnavani vstupu
		function __construct()
		{
			$this->int_reg = 'int@(\+|-|)[0-9]+';
			$this->bool_reg = 'bool@(true|false)';
			$this->comment_reg = '(#.*|)';
			$this->var_reg = '(LF|GF|TF)@[\w_\-$&%\*]+';
			$this->string_reg = 'string@([^\s^\\\\^#^\x00-\x20]|\\\\[0-9][0-9][0-9])*';
			$this->label_reg = '[\w_\-$&%\*]+';
			$this->type_reg = '(int|bool|string)';
			$this->symb_reg = "(".$this->var_reg."|".$this->bool_reg."|".$this->int_reg."|".$this->string_reg.")";
			$this->check = array(
				"/^\s*\.IPPcode18\s*".$this->comment_reg."/",
				"/^\s*(?i)MOVE(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)CREATEFRAME(?-i)\s*".$this->comment_reg."$/",
				"/^\s*(?i)PUSHFRAME(?-i)\s*".$this->comment_reg."$/",
				"/^\s*(?i)POPFRAME(?-i)\s*".$this->comment_reg."$/",
				"/^\s*(?i)DEFVAR(?-i)\s+".$this->var_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)CALL(?-i)\s+".$this->label_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)RETURN(?-i)\s*".$this->comment_reg."$/",
				"/^\s*(?i)PUSHS(?-i)\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)POPS(?-i)\s+".$this->var_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)ADD(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)SUB(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)MUL(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)IDIV(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)LT(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)GT(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)EQ(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)AND(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)OR(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)NOT(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)INT2CHAR(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)STRI2INT(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)READ(?-i)\s+".$this->var_reg."\s+".$this->type_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)WRITE(?-i)\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)CONCAT(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)STRLEN(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)GETCHAR(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)SETCHAR(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)TYPE(?-i)\s+".$this->var_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)LABEL(?-i)\s+".$this->label_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)JUMP(?-i)\s+".$this->label_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)JUMPIFEQ(?-i)\s+".$this->label_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)JUMPIFNEQ(?-i)\s+".$this->label_reg."\s+".$this->symb_reg."\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)DPRINT(?-i)\s+".$this->symb_reg."\s*".$this->comment_reg."$/",
				"/^\s*(?i)BREAK(?-i)\s*".$this->comment_reg."$/",
				"/^\s*".$this->comment_reg."$/"
			);

			//nacte a zkontroluje prvni radek ".IPPcode18"
			$this->input = trim(fgets(STDIN));
			while(!preg_match("/^(?i)\.IPPcode18(?-i)\s*".$this->comment_reg."/", $this->input)){
				if(feof(STDIN)){
					fwrite(STDERR, "Chyba ve vstupnim kodu => $this->input \n");
					exit(21);
				}
				$this->input = trim(fgets(STDIN));
			}
		}

		//zkontroluje radek
		public function check_row()
		{
			foreach ($this->check as $key) {
				if(preg_match($key, $this->input)){
					return true;
				}
			}
		}

		//nacte radek a zavola kontrolu radku
		public function load_row()
		{
			if(feof(STDIN))
				return 0;
			$this->input = trim(fgets(STDIN));
			if($this->check_row() == false){
				fwrite(STDERR, "Chyba ve vstupnim kodu => $this->input \n");
				exit(21);
			}
			$split = explode('#', $this->input);
			$this->input = $split[0];
			return 1;
		}

	}

	/**
	* @brief trida na zpracovani vystupu ve forme XML
	*/
	class XML_generate
	{
		public $stream;
		protected $counter;

			/**
		* @brief funkce zpracuje argument do xml podoby
		* @param xml_stream promenna do ktere se zapisuje xml (pomoci kihovny XMLWriter)
		* @param varname jmeno promenne
		* @param type typ promenne
		* @param arg_num retezec urcujici poradi argumentu
		*/
		protected function xml_arg($varname, $type, $arg_num){
			$this->stream->startElement("$arg_num");
			$this->stream->startAttribute('type');
			$this->stream->text("$type");
			$this->stream->endAttribute();
			$this->stream->text("$varname");
			$this->stream->endElement();
		}
		/**
		* @brief funkce zpracuje argument <symb> do xml podoby
		* @param xml_stream promenna do ktere se zapisuje xml (pomoci kihovny XMLWriter)
		* @param varname jmeno promenne
		* @param arg_num retezec urcujici poradi argumentu
		*/
		protected function xml_symb($varname, $arg_num){
			$this->stream->startElement("$arg_num");
			$this->stream->startAttribute('type');
			$type = explode('@', $varname,2);
			if($type[0] == "GF" || $type[0] == "LF" || $type[0] == "TF"){
				$this->stream->text("var");
				$this->stream->endAttribute();
				$this->stream->text("$varname");
			}
			else{
				$this->stream->text("$type[0]");
				$this->stream->endAttribute();
				$this->stream->text("$type[1]");
			}
			$this->stream->endElement();
		}

		/**
		* @brief funkce zpracuje instrukci
		* @param line radek s instrukci
		*/
		public function gener_instruction($line){
			$words = preg_split("/\s+/", $line,-1,PREG_SPLIT_NO_EMPTY);

			$this->stream->startElement('instruction');
			$this->stream->startAttribute('order');
			$this->stream->text("$this->counter");
			$this->counter = $this->counter + 1;
			$this->stream->endAttribute();
			$this->stream->startAttribute('opcode');
			$upword = strtoupper($words[0]);
			$this->stream->text("$upword");
			$this->stream->endAttribute();
			switch ($upword) {
				case 'MOVE':
					//arg1
					$this->xml_arg($words[1], "var","arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					break;
				case 'DEFVAR':
					$this->xml_arg($words[1], "var", "arg1");
					break;
				case 'CALL':
					$this->xml_arg($words[1], "label", "arg1");
					break;
				case 'PUSHS':
					$this->xml_symb($words[1],"arg1");
					break;
				case 'POPS':
					$this->xml_arg($words[1], "var", "arg1");
					break;
				case 'ADD':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'SUB':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'MUL':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'IDIV':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'LT':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'GT':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'EQ':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'AND':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'OR':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'NOT':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					break;
				case 'INT2CHAR':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					break;
				case 'STRI2INT':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'READ':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_arg($words[2], "type", "arg2");
					break;
				case 'WRITE':
					$this->xml_symb($words[1],"arg1");
					break;
				case 'CONCAT':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'STRLEN':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					break;
				case 'GETCHAR':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'SETCHAR':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'TYPE':
					//arg1
					$this->xml_arg($words[1], "var", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					break;
				case 'LABEL':
					//arg1
					$this->xml_arg($words[1], "label", "arg1");
					break;
				case 'JUMP':
					//arg1
					$this->xml_arg($words[1], "label", "arg1");
					break;
				case 'JUMPIFEQ':
					//arg1
					$this->xml_arg($words[1], "label", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'JUMPIFNEQ':
					//arg1
					$this->xml_arg($words[1], "label", "arg1");
					//arg2
					$this->xml_symb($words[2],"arg2");
					//arg3
					$this->xml_symb($words[3],"arg3");
					break;
				case 'DPRINT':
					$this->xml_symb($words[1],"arg1");
					break;
				default:
					#echo "bar";
					break;
			}
			$this->stream->endElement();// instruction
		}

		//init trridy
		function __construct()
		{
			//zahajeni psani xml, hlavicka
			$this->stream = new XMLWriter;
			$this->stream->openMemory();
			$this->stream->setIndent(true);
			$this->stream->setIndent('   ');
			$this->stream->startDocument('1.0', 'UTF-8');
			$this->counter = 1;

			//start xml program
			$this->stream->startElement('program');
			$this->stream->startAttribute('language');
			$this->stream->text('IPPcode18');
			$this->stream->endAttribute();
		}

		//funkce na ukonceni a vytisknuti xml
		public function end_stream(){
			$this->stream->endElement(); // program
			$this->stream->endDocument();
			echo $this->stream->outputMemory();
		}
	}


	//Zacatek main
	$options = getopt(null, array("", "help"));
	if(count($options)){
		echo "Program příjme na ze standartního vstupu kod v jazyce IPPcode18 a vydá na výstup jeho XML reprezentaci";
		return;
	}


	$instruction = new Row();
	$xml = new XML_generate();

	while($instruction->load_row()){
		if($instruction->input != "")
			$xml->gener_instruction($instruction->input);
	}
	$xml->end_stream();
	exit(0);
?>