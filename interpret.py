import getopt
import xml.etree.ElementTree as ElementTree
import re
import sys
import codecs

#Nacte a zpracuje xml vstup
class XMLoad:
	def __init__(self,file):
		try:
			self.xml = ElementTree.parse(file)
		except:
			print("Problem s nacitanim souboru",file=sys.stderr)
			exit(11)
		self.root = self.xml.getroot()

	#zkontroluje nazev entit argumentu
	def checkArg(self,instr):
		result = True
		count = 0
		list1 = ['arg1','arg2','arg3']
		for arg in instr:
			if arg.tag in list1:
				list1.remove(arg.tag)
			else:
				return False
			result = result and 'type' in arg.attrib
			count += 1
		if count == 0:
			result = result and 'arg1' in list1
			result = result and 'arg2' in list1 
			result = result and 'arg3' in list1 
		elif count == 1:
			result = result and 'arg2' in list1 
			result = result and 'arg3' in list1 
			result = result and not 'arg1' in list1
		elif count == 2:
			result = result and 'arg3' in list1
			result = result and not 'arg2' in list1
			result = result and not 'arg1' in list1
		elif count == 3:
			result = result and not 'arg3' in list1
			result = result and not 'arg2' in list1
			result = result and not 'arg1' in list1
		else:
			return False
		return result

	#zkontroluje entitu instruction (atributy)
	def checkInstruction(self):
		result = True
		for instr in self.root:
			result = result and 'instruction' == instr.tag
			result = result and 'opcode' in instr.attrib
			result = result and 'order' in instr.attrib
			result = result and self.checkArg(instr)
		return result

	#zkontroluje hlavicku a nasledne vola checkInstruction pro vsechny instrukce
	def checkXML(self):
		result = 'program' == self.root.tag
		result = result and 'language' in self.root.attrib
		result = result and 'IPPcode18' == self.root.attrib.get('language')
		supp = True
		if len(self.root.attrib) == 2:
			supp = supp and 'name' in self.root.attrib
			supp = supp or 'description' in self.root.attrib
		elif len(self.root.attrib) == 3:
			supp = supp and 'name' in self.root.attrib
			supp = supp and 'description' in self.root.attrib
		return  result and supp and self.checkInstruction()

	#seradi instrukce od nejmensiho opcode po nejvetsi a vrati serazeny list
	def sortIstr(self):
		sortedList = []
		position = 1
		for child in self.root:
			for instr in self.root:
				if int(instr.attrib.get('order')) == position:
					sortedList.append(instr)
					break
			position += 1
		return sortedList

#zkontroluje lexikalni a syntaktickou spravnost xml vsupu
class LSAnalyse:
	keywords = ['MOVE','CREATEFRAME', 'PUSHFRAME', 'POPFRAME', 'DEFVAR', 'CALL', 'RETURN',
				'PUSHS', 'POPS', 'ADD', 'SUB', 'MUL', 'IDIV', 'LT', 'GT', 'EQ', 'AND', 'OR', 
				'NOT', 'INT2CHAR', 'STRI2INT', 'READ', 'WRITE', 'CONCAT', 'STRLEN', 'GETCHAR', 
				'SETCHAR', 'TYPE', 'LABEL', 'JUMP', 'JUMPIFEQ', 'JUMPIFNEQ', 'DPRINT', 'BREAK']
	patternInt = r'(\+|-|)[0-9]+'
	patternBool = r'(true|false)'
	patternString = r'([^\s^\\\\^#^\x00-\x20]|\\\\[0-9][0-9][0-9])*'
	# chyba need to fix
	patternVar = r'(LF|GF|TF)@[\w_\-$&%\*]+'
	patternLabel = r'[\w_\-$&%\*]+'
	patternType = r'(int|bool|string)'

	#zkontroluje jestli instrukce existuje a jestli typy souhlasi s hodnotou
	def analyseLex(self,instr):
		result = instr.attrib.get('opcode') in self.keywords
		for arg in instr:
			if 'int' == arg.attrib.get('type'):
				result = result and True if re.match(self.patternInt, arg.text) else False
			elif 'string' == arg.attrib.get('type'):
				if not arg.text:
					result = True
				else:
					result = result and True if re.match(self.patternString, arg.text) else False
			elif 'bool' == arg.attrib.get('type'):
				result = result and True if re.match(self.patternBool, arg.text) else False
			elif 'var' == arg.attrib.get('type'):
				result = result and True if re.match(self.patternVar, arg.text) else False
			elif 'label' == arg.attrib.get('type'):
				result = result and True if re.match(self.patternLabel, arg.text) else False
			elif 'type' == arg.attrib.get('type'):
				result = result and True if re.match(self.patternType, arg.text) else False
			else:
				result = False
		return result

	#zkontroluje spravny pocet argumentu u instrukce
	def checkArgSyn(self, instr, count, args):
		actCount = 0
		result = True
		for arg in instr:
			if actCount < count:
				if args[actCount] == 'symb':
					isSymb = arg.attrib.get('type') == 'int' or arg.attrib.get('type') == 'bool'
					isSymb = isSymb or arg.attrib.get('type') == 'string' or arg.attrib.get('type') == 'var'
					result = result and isSymb
				else:
					result = result and args[actCount] == arg.attrib.get('type')
				actCount += 1
			else:
				return False
		if actCount == count:
			return result
		else:
			return False


	#zavola checkArgSyn podle nazvu instrukce
	def analyseSyn(self,instr):
		result = False
		oper = instr.attrib.get('opcode')
		if oper == 'MOVE':
			result = self.checkArgSyn(self, instr, 2, ['var', 'symb'])
		elif oper == 'CREATEFRAME':
			result = self.checkArgSyn(self, instr, 0, [])
		elif oper == 'PUSHFRAME':
			result = self.checkArgSyn(self, instr, 0, [])
		elif oper == 'POPFRAME':
			result = self.checkArgSyn(self, instr, 0, [])
		elif oper == 'DEFVAR':
			result = self.checkArgSyn(self, instr, 1, ['var'])
		elif oper == 'CALL':
			result = self.checkArgSyn(self, instr, 1, ['label'])
		elif oper == 'RETURN':
			result = self.checkArgSyn(self, instr, 0, [])
		elif oper == 'PUSHS':
			result = self.checkArgSyn(self, instr, 1, ['symb'])
		elif oper == 'POPS':
			result = self.checkArgSyn(self, instr, 1, ['var'])
		elif oper == 'ADD':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'SUB':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'MUL':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'IDIV':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'LT':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'GT':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'EQ':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'AND':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'OR':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'NOT':
			result = self.checkArgSyn(self, instr, 2, ['var', 'symb'])
		elif oper == 'INT2CHAR':
			result = self.checkArgSyn(self, instr, 2, ['var', 'symb'])
		elif oper == 'STRI2INT':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'READ':
			result = self.checkArgSyn(self, instr, 2, ['var', 'type'])
		elif oper == 'WRITE':
			result = self.checkArgSyn(self, instr, 1, ['symb'])
		elif oper == 'CONCAT':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'STRLEN':
			result = self.checkArgSyn(self, instr, 2, ['var', 'symb'])
		elif oper == 'GETCHAR':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'SETCHAR':
			result = self.checkArgSyn(self, instr, 3, ['var', 'symb', 'symb'])
		elif oper == 'TYPE':
			result = self.checkArgSyn(self, instr, 2, ['var', 'symb'])
		elif oper == 'LABEL':
			result = self.checkArgSyn(self, instr, 1, ['label'])
		elif oper == 'JUMP':
			result = self.checkArgSyn(self, instr, 1, ['label'])
		elif oper == 'JUMPIFEQ':
			result = self.checkArgSyn(self, instr, 3, ['label', 'symb', 'symb'])
		elif oper == 'JUMPIFNEQ':
			result = self.checkArgSyn(self, instr, 3, ['label', 'symb', 'symb'])
		elif oper == 'DPRINT':
			result = self.checkArgSyn(self, instr, 1, ['symb'])
		elif oper == 'BREAK':
			result = self.checkArgSyn(self, instr, 0, [])
		else:
			return False
		return result
		

	#projde vsechny instrukce a zavola na ne kontrolovaci metody
	def analyseInstr(self,tree):
		for instr in tree:
			if self.analyseLex(self,instr) == False:
				return False

			if self.analyseSyn(self,instr) == False:
				return False
		return True
#konec LSAnalyse


#trida, ktera interpretuje instrukce na vystup
class Interpret:
	# name : [ type, value]
	tempFrame = {}
	TFAlloc = False
	globalFrame = {}
	currentLFrame = {}
	LFAlloc = False
	frameStack = []
	labels = {}
	stackValue = []
	callStack = []


	#konvertuje hodnotu na patricny typ
	def convertType(var):
		if var[0] == 'int':
			var[1] = int(var[1])
		elif var[0] == 'bool':
			var[1] = var[1] == 'true'
		elif var[0] == 'string' and not var[1]:
			var[1] = ''
		return var


	#zkontroluje jestli promenna existuje
	def varExist(self, var):
		var = var.split('@')
		checkVar = True
		if var[0] == 'GF':
			checkVar = var[1] in self.globalFrame
		elif var[0] == 'LF':
			if self.LFAlloc == False:
				print("Lokalni ramec neni vytvoren",file=sys.stderr)
				exit(55)
			checkVar = var[1] in self.currentLFrame
		elif var[0] == 'TF':
			if self.TFAlloc == False:
				print("Docasny ramec neni vytvoren",file=sys.stderr)
				exit(55)
			checkVar = var[1] in self.tempFrame
		if checkVar == False:
			print("Promenna neni v ramci",file=sys.stderr)
			exit(54)

	#najde promennou v ramci podle nazvu
	def getVar(self, var):
		self.varExist(self,var)
		var = var.split('@', 1)
		if var[0] == 'GF':
			var = self.globalFrame[var[1]]
		elif var[0] == 'LF':
			var = self.currentLFrame[var[1]]
		elif var[0] == 'TF':
			var = self.tempFrame[var[1]]
		return var

	#aktualizuje promennou v ramci podle nazvu
	def updateVar(self,var, src):
		self.varExist(self,var)
		var = var.split('@',1)
		if var[0] == 'GF':
			self.globalFrame[var[1]] = src
		elif var[0] == 'LF':
			self.currentLFrame[var[1]] = src
		elif var[0] == 'TF':
			self.tempFrame[var[1]] = src

	def checkTypes(var1, var2, typeExp):
		result = var1[0] == var2[0]
		result = result and var1[0] == typeExp
		if result == False:
			exit(53)


	#instrukce MOVE
	def move(self,instr):
		varPos = 1
		src = 0
		dest = 0
		for arg in instr:
			if varPos == 1:
				dest = arg.text
			elif varPos == 2:
				src = [arg.attrib.get('type'), arg.text]
			varPos += 1
		if src[0] == 'var':
			src = self.getVar(self,src[1])
		else:
			src = self.convertType(src)

		self.updateVar(self, dest, src)

	#instrukce CREATEFRAME
	def createFrame(self):
		self.TFAlloc = True
		self.tempFrame.clear()

	#instrukce PUSHFRAME
	def pushFrame(self):
		if self.TFAlloc == False:
			print("PUSHFRAME: Docasny ramec je prazdny",file=sys.stderr)
			exit(55)

		if self.LFAlloc == False:
			self.LFAlloc = True
		else:
			self.frameStack.append(self.currentLFrame.copy())

		self.currentLFrame = self.tempFrame.copy()
		self.TFAlloc = False

	#instrukce POPFRAME
	def popFrame(self):
		if len(self.frameStack) == 0 and self.LFAlloc == False:
			print("POPFRAME: Zasobnik docasnych ramcu je prazdny",file=sys.stderr)
			exit(55)

		self.tempFrame = self.currentLFrame.copy()
		self.TFAlloc = True
		if len(self.frameStack) == 0:
			self.LFAlloc = False
			self.currentLFrame.clear()
		else:
			self.currentLFrame = self.frameStack.pop()

	#instrukce DEFVAR
	def defVar(self, arg):
		var = arg.text
		var = var.split('@',1)
		temp = {var[1]: ['none', '0']}
		if var[0] == 'GF':
			self.globalFrame.update(temp)
		elif var[0] == 'LF':
			if self.LFAlloc == False:
				print("DEFVAR: Lokalni ramec neni vytvoren",file=sys.stderr)
				exit(55)
			self.currentLFrame.update(temp)
		elif var[0] == 'TF':
			if self.TFAlloc == False:
				print("DEFVAR: Docasny ramec neni vytvoren",file=sys.stderr)
				exit(55)
			self.tempFrame.update(temp)

	#metona na konverzi escape sekvenci
	def convertEsc(string):
		result = string
		escapes = re.findall(r'\\[0-9][0-9][0-9]',string)
		while len(escapes):
			char = escapes[0].replace('\\','')
			char = chr(int(char))
			result = result.replace(escapes[0],char)
			escape = escapes[0]
			while escapes.count(escape) > 0:
				escapes.remove(escape)
		return result



	#instrukce WRITE
	def write(self,arg,err):
		output = 0
		var = 0
		typeS = arg.attrib.get('type') 
		if typeS == 'var':
			var = self.getVar(self, arg.text)
			if var[0] == 'none':
				exit(56)
			elif var[0] == 'bool':
				if var[1] == True:
					var[1] = 'true'
				else:
					var[1] = 'false'
			typeS = var[0]
			output = var[1]
		else:
			output = arg.text

		if typeS == 'string':
			output = self.convertEsc(output)
		if err:
			print(output, file=sys.stderr)
		else:
			print(output)


	def doOper(self,val1,val2,oper):
		if oper == 'ADD':
			self.checkTypes(val1,val2,'int')
			result = val1[1] + val2[1]
			return [val1[0],result]
		elif oper == 'SUB':
			self.checkTypes(val1,val2,'int')
			result = val1[1] - val2[1]
			return [val1[0],result]
		elif oper == 'MUL':
			self.checkTypes(val1,val2,'int')
			result = val1[1] * val2[1]
			return [val1[0],result]
		elif oper == 'IDIV':
			self.checkTypes(val1,val2,'int')
			if val2[1] == 0:
				exit(57)
			result = val1[1] // val2[1]
			return [val1[0],result]
		elif oper == 'AND':
			self.checkTypes(val1,val2,'bool')
			result = val1[1] and val2[1]
			return [val1[0],result]
		elif oper == 'OR':
			self.checkTypes(val1,val2,'bool')
			result = val1[1] or val2[1]
			return [val1[0],result]
		elif oper == 'NOT':
			self.checkTypes(val1,['bool', False],'bool')
			result = not val1[1]
			return [val1[0],result]
		elif oper == 'LT':
			self.checkTypes(val1,val2,val2[0])
			result = val1[1] < val2[1]
			return ['bool',result]
		elif oper == 'GT':
			self.checkTypes(val1,val2,val2[0])
			result = val1[1] > val2[1]
			return ['bool',result]
		elif oper == 'EQ':
			self.checkTypes(val1,val2,val2[0])
			result = val1[1] == val2[1]
			return ['bool',result]
		elif oper == 'CONCAT':
			self.checkTypes(val1,val2,'string')
			result = val1[1] + val2[1]
			return [val1[0],result]
		elif oper == 'STRLEN':
			self.checkTypes(val1,val1,'string')
			val1[1] = self.convertEsc(val1[1])
			result = len(val1[1])
			return ['int',result]
		elif oper == 'INT2CHAR':
			self.checkTypes(val1,val1,'int')
			try:
				result = chr(val1[1])
			except ValueError:
				print("INT2CHAR: Celociselna hodnota nelze prevest na Unicode", file=sys.stderr)
				exit(58)
			return ['string', result]
		elif oper == 'STRI2INT':
			self.checkTypes(val1,val1,'string')
			self.checkTypes(val2,val2,'int')
			if val2[1] < 0 or val2[1] >= len(val1[1]):
				print("STRI2INT: Indexovani mimo retezec", file=sys.stderr)
				exit(58)
			val1[1] = self.convertEsc(val1[1])
			strList = list(val1[1])
			result = ord(strList[val2[1]])
			return ['int', result]
		elif oper == 'GETCHAR':
			self.checkTypes(val1,val1,'string')
			self.checkTypes(val2,val2,'int')
			if val2[1] < 0 or val2[1] >= len(val1[1]):
				print("GETCHAR: Indexovani mimo retezec", file=sys.stderr)
				exit(58)
			val1[1] = self.convertEsc(val1[1])
			strList = list(val1[1])
			result = strList[val2[1]]
			return ['string', result]


	def prepOper(self,instr):
		var = 0
		symb1 = 0
		symb2 = 0
		for arg in instr:
			if arg.tag == 'arg1':
				var = arg.text
			elif arg.tag == 'arg2':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb1 = self.getVar(self,arg.text)
					if symb1[0] == 'none':
						exit(56)
				else:
					symb1 = self.convertType([typeS,arg.text])
			elif arg.tag == 'arg3':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb2 = self.getVar(self,arg.text)
					if symb2[0] == 'none':
						exit(56)
				else:
					symb2 = self.convertType([typeS,arg.text])
		self.updateVar(self,var,self.doOper(self,symb1,symb2,instr.attrib.get('opcode')))


	def loadLabels(self,instrs):
		callList = []
		jumpOpers = ['JUMP', 'CALL', 'JUMPIFEQ', 'JUMPIFNEQ']
		for instr in instrs:
			oper = instr.attrib.get('opcode')
			if oper == 'LABEL':
				pos = instr.attrib.get('order')
				for arg in instr:
					if arg.text in self.labels:
						print("LABELS: redefinice navesti",file=sys.stderr)
						exit(52)
					self.labels.update({arg.text: int(pos)-1})
			elif oper in jumpOpers:
				pos = instr.attrib.get('order')
				for arg in instr:
					if arg.tag == 'arg1':
						callList.append(arg.text)
		for call in callList:
			if not call in self.labels:
				print("Pokus o skok na neexistujici navesti",file=sys.stderr)
				exit(52)

	#instrukce SETCHAR
	def setChar(self,instr):
		var = 0
		varValues = 0
		symb1 = 0
		symb2 = 0
		for arg in instr:
			if arg.tag == 'arg1':
				var = arg.text
				varValues = self.getVar(self,var)
				if varValues[0] == 'none':
					print("Hodnota neni inicializovana", file=sys.stderr)
					exit(56)
				elif varValues[0] != 'string':
					print("SETCHAR: Hodnota arg1 ma spatny typ", file=sys.stderr)
					exit(53)
			elif arg.tag == 'arg2':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb1 = self.getVar(self,arg.text)
					if symb1[0] == 'none':
						exit(56)
				else:
					symb1 = self.convertType([typeS,arg.text])
				if symb1[0] != 'int':
					print("SETCHAR: Hodnota arg2 ma spatny typ", file=sys.stderr)
					exit(53)
			elif arg.tag == 'arg3':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb2 = self.getVar(self,arg.text)
					if symb2[0] == 'none':
						exit(56)
				else:
					symb2 = self.convertType([typeS,arg.text])
				if symb2[0] != 'string':
					print("SETCHAR: Hodnota arg3 ma spatny typ", file=sys.stderr)
					exit(53)

		if symb1[1] < 0 or symb1[1] >= len(varValues[1]):
			print("SETCHAR: Index je mimo retezec", file=sys.stderr)
			exit(58)
		elif not symb2[1]:
			print("SETCHAR: Symb2 je prazdny", file=sys.stderr)
			exit(58)
		varValues[1] = self.convertEsc(varValues[1])
		symb2[1] = self.convertEsc(symb2[1])
		strList1 = list(varValues[1])
		strList2 = list(symb2[1])
		strList1[symb1[1]] = strList2[0]
		varValues[1] = ''.join(strList1)
		self.updateVar(self,var,varValues)
		
	#instrukce TYPE
	def type(self,instr):
		var = 0
		symb = 0
		for arg in instr:
			if arg.tag == 'arg1':
				var = arg.text
			elif arg.tag == 'arg2':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb = self.getVar(self,arg.text)
				else:
					symb = [typeS,arg.text]
		if symb[0] == 'none':
			self.updateVar(self,var,['string',''])
		else:
			self.updateVar(self,var,['string',symb[0]])
	
	def pushs(self, arg):
		temp = []
		typeS = arg.attrib.get('type')
		if typeS == 'var':
			temp = self.getVar(self,arg.text)
		else:
			temp = [typeS,self.convertType(arg)]
		self.stackValue.append(temp)

	def pops(self,arg):
		if len(self.stackValue) == 0:
			print("POPS: Zasobnik je prazdny", file=sys.stderr)
			exit(56)
		self.updateVar(self,arg.text,self.stackValue.pop())

	def breakFunc(self,position,count):
		print('Aktualni pozice v kodu: ' + position,file=sys.stderr)
		print(' ',file=sys.stderr)
		print('Aktualni obsah ramcu: ',file=sys.stderr)
		print('Docasny ramec:',file=sys.stderr)
		print(self.tempFrame,file=sys.stderr)
		print(' ',file=sys.stderr)
		print('Lokalni ramec: ',file=sys.stderr)
		print('Aktivni: ',file=sys.stderr)
		print(self.currentLFrame,file=sys.stderr)
		print('Ostatni: ',file=sys.stderr)
		for frame in self.frameStack:
			print(frame,file=sys.stderr)
		print(' ',file=sys.stderr)
		print('Globalni ramec:',file=sys.stderr)
		print(self.globalFrame,file=sys.stderr)
		print(' ',file=sys.stderr)
		print('Pocet vykonanych instrukci: ' + str(count),file=sys.stderr)
		print('Aktivni docasny ramec ' + str(self.TFAlloc),file=sys.stderr)
		print('Aktivni lokalni ramec ' + str(self.LFAlloc),file=sys.stderr)


	def read(self,instr):
		var = 0
		typeS = 0
		string = ''
		for arg in instr:
			if arg.tag == 'arg1':
				var = arg.text
			if arg.tag == 'arg2':
				typeS = arg.text
		try:
			string = input()
			if typeS == 'bool':
				if string == 'true':
					string = True
				else:
					string = False
			elif typeS == 'int':
				if re.match(r'(\+|-|)[0-9]+',string):
					string = int(string)
				else:
					string = 0
		except EOFError:
			if typeS == 'bool':
				string = False
			elif typeS == 'int':
				string = 0
		self.updateVar(self,var,[typeS,string])


	def jumpIF(self,instr):
		label = 0
		symb1 = 0
		symb2 = 0
		for arg in instr:
			if arg.tag == 'arg1':
				label = arg.text
			elif arg.tag == 'arg2':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb1 = self.getVar(self,arg.text)
				else:
					symb1 = self.convertType([typeS,self.arg.text])
			elif arg.tag == 'arg3':
				typeS = arg.attrib.get('type')
				if typeS == 'var':
					symb2 = self.getVar(self,arg.text)
				else:
					symb2 = self.convertType([typeS,arg.text])
		self.checkTypes(symb1,symb2,symb2[0])
		jump = instr.attrib.get('opcode')
		result = False
		if jump == 'JUMPIFEQ':
			result = symb1[1] == symb2[1]
		elif jump == 'JUMPIFNEQ':
			result = symb1[1] != symb2[1]

		if result:
			return self.labels[label]
		else:
			return int(instr.attrib.get('order')) - 1

	######## require more testing ###########
	def call(self,arg,position):
		self.callStack.append(position)
		return self.labels[arg.text]

	def returnFunc(self):
		if len(self.callStack) == 0:
			print('RETURN: prazdny zasobnik volani',file=sys.stderr)
			exit(56)
		return self.callStack.pop()
	######## require more testing ###########

	#hlavni smycka interpretu
	def program(self,instrList):
		operList = ['ADD', 'SUB', 'MUL', 'IDIV', 'AND', 'OR', 'NOT', 'LT', 'GT', 'EQ', 'CONCAT', 'STRLEN', 'INT2CHAR', 'STRI2INT', 'GETCHAR']
		position = 0
		countInstr = 1
		self.loadLabels(self,instrList)
		while position < len(instrList):
			oper = instrList[position].attrib.get('opcode')
			if oper == 'MOVE':
				self.move(self,instrList[position])
			elif oper == 'CREATEFRAME':
				self.createFrame(self)
			elif oper == 'PUSHFRAME':
				self.pushFrame(self)
			elif oper == 'POPFRAME':
				self.popFrame(self)
			elif oper == 'DEFVAR':
				for arg in instrList[position]:
					self.defVar(self,arg)
			elif oper == 'WRITE':
				for arg in instrList[position]:
					self.write(self,arg,False)
			elif oper == 'SETCHAR':
				self.setChar(self,instrList[position])
			elif oper == 'TYPE':
				self.type(self,instrList[position])
			elif oper == 'PUSHS':
				for arg in instrList[position]:
					self.pushs(self,arg)
			elif oper == 'POPS':
				for arg in instrList[position]:
					self.pops(self,arg)
			elif oper == 'READ':
				self.read(self,instrList[position])
			elif oper == 'DPRINT':
				for arg in instrList[position]:
					self.write(self,arg,True)
			elif oper == 'BREAK':
				self.breakFunc(self,instrList[position].attrib.get('order'),countInstr)
			elif oper == 'JUMP':
				for label in instrList[position]:
					position = self.labels[label.text]
			elif oper == 'JUMPIFEQ' or oper == 'JUMPIFNEQ':
				position = self.jumpIF(self,instrList[position])
			elif oper == 'CALL':
				for arg in instrList[position]:
					position = self.call(self,arg,position)
			elif oper == 'RETURN':
				position = self.returnFunc(self)
			elif oper in operList:
				self.prepOper(self,instrList[position])

			position += 1
			countInstr += 1



		
#main
if len(sys.argv) != 2:
	print('Nespravny pocet argumentu',file=sys.stderr)
	exit(10)
try:
	opts = getopt.getopt(sys.argv[1:],"",["help", "source="])
except:
	print("Nespravny format argumentu",file=sys.stderr)
	exit(10)
if '--source' == opts[0][0][0]:
	xml = XMLoad(opts[0][0][1])
	if xml.checkXML() == False:
		exit(31)

	if LSAnalyse.analyseInstr(LSAnalyse,xml.root) == False:
		exit(32)

	instr = xml.sortIstr()
	Interpret.program(Interpret,instr)
elif '--help' == opts[0][0][0]:
	print('Interpretuje vstupni xml na program')
	print('Pouziti: interpret.py --source=file | --help ')
	print('Je vyzadovan jeden z argumentu')
	print('--source=file ..... vstupni soubor, kde file je nazev(cesta) k souboru')
	print('--help ..... vypise napovedu')
	exit(0)
else:
	print('Nespravny argument',file=sys.stderr)
	exit(10)


