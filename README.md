# IPP
## Autor zdrojových kódů: Jan Vávra

## Zadání
### 1. část - Analyzátor kódu v IPPcode19 (parse.php)

Skript typu filtr (parse.php v jazyce PHP 7.3) načte ze standardního vstupu zdrojový kód v IPPcode19, 
zkontroluje lexikální a syntaktickou správnost kódu a vypíše na standardní výstup XML reprezentaci programu dle 
specifikace v sekci 1.2.
Tento skript bude pracovat s těmito parametry:
- --help - nápověda programu
Chybové návratové kódy specifické pro analyzátor:
- 21 - chybná nebo chybějící hlavička ve zdrojovém kódu zapsaném v IPPcode19;
- 22 - neznámý nebo chybný operační kód ve zdrojovém kódu zapsaném v IPPcode19;
- 23 - jiná lexikální nebo syntaktická chyba zdrojového kódu zapsaného v IPPcode19.

#### 1.2 Popis výstupního XML formátu

Za povinnou XML hlavičkou následuje kořenový elementprogram (s povinným textovým atributem `languages` hodnotou IPPcode19), 
který obsahuje pro instrukce elementy `instruction`. Každý element `instruction` obsahuje povinný atribut `orders` pořadím 
instrukce (počítáno od 1) a povinný atribut `opcode` (hodnota operačního kódu je ve výstupním XML vždy velkými písmeny) 
a elementypro odpovídající počet operandů/argumentů: `arg1` pro případný první argument instrukce, `arg2` pro případný 
druhý argument a `arg3` pro případný třetí argument instrukce. Element pro argument má povinný atribut `type` s možnými 
hodnotami `int,bool,string,nil,label,type,var` podle toho,zda se jedná o literál, návěští, typ nebo proměnnou, 
a obsahuje textový element. Tento textový element potom nese buď hodnotu literálu (již bez určení typu a bez znaku @),
nebo jméno návěští, nebo typ, nebo identifikátor proměnné (včetně určení rámce a @). U proměnných ponechávejte označení 
rámce vždy velkými písmeny (samotné jméno proměnné ponechejte bezezměny). V případě číselných literálů je zápis ponechán 
ve formátu ze zdrojového kódu (např. zůstanou kladná znaménka čísel nebo počáteční přebytečné nuly) a není třeba kontrolovat 
jejich lexikální správnost (na rozdíl od řetězcových literálů). U literálů typu `string` při zápisu do XML nepřevádějte
původní escape sekvence, ale pouze pro problematické znakyv XML (např.<,>, &) využijte odpovídající XML entity 
(např.&lt;,&gt;,&amp;). Podobně převádějte problematické znaky vyskytujícíse v identifikátorech proměnných. 
Literály typu `bool` vždy zapisujte malými písmeny jako `false` nebo `true`.

### 2. část - Interpret XML reprezentace kódu (interpret.py)

Program načte XML reprezentaci programu ze zadaného souboru a tento program s využitím standardního vstupu 
a výstupu interpretuje. Vstupní XML reprezentace je např. generována skriptem `parse.php` (ale ne nutně) ze 
zdrojového kódu v IPPcode19. Interpret navíc oproti sekci 1.2 podporuje existenci volitelných dokumentačních 
textových atributů `name` a `description` v kořenovém elementu `program`.

Tento skript bude pracovat s těmito parametry:
- --help nápověda pro spuštění;
- --source=file - vstupní soubor s XML reprezentací zdrojového kódu dle definice ze sekce 1.2;
- --input=file - soubor se vstupy pro samotnou interpretaci zadaného zdrojového kódu.

Alespoň jeden z parametrů (--source nebo --input) musí být vždy zadán. Pokud jeden z nich chybí, tak 
jsou odpovídající data načítána ze standardního vstupu.

Chybové návratové kódy specifické pro interpret:
- 31 - chybný XML formát ve vstupním souboru (soubor není tzv. dobře formátovaný, angl.well-formed);
- 32 - neočekávaná struktura XML či lexikální nebo syntaktická chyba textových elementů a atributů 
ve vstupním XML souboru (např. chybný lexém pro řetězcový literál, neznámý operační kód apod.).

Chybové návratové kódy interpretu v případě chyby během interpretace jsou uvedeny v popisu jazyka IPPcode19.

### 3. část - Testovací rámec (test.php)

Skript (test.php v jazyce PHP 7.3) bude sloužit pro automatické testování postupné aplikace `parse.php` a `interpret.py`. 
Skript projde zadaný adresář s testy a využije je pro automatické otestování správné funkčnosti obou předchozích 
programů včetně vygenerování přehledného souhrnu v HTML 5 do standardního výstupu. Testovací skript nemusí 
u předchozích dvou skriptů testovat jejich dodatečnou funkčnost aktivovanou parametry příkazové řádky 
(s výjimkou potřeby parametru --source a/nebo --input u interpret.py). 

Tento skript bude pracovat s těmito parametry:
- --help - nápověda programu;
- --directory=path - testy bude hledat v zadaném adresáři (chybí-li tento parametr, tak skript prochází aktuální adresář);
- --recursive - testy bude hledat nejen v zadaném adresáři, ale i rekurzivněve všech jeho podadresářích;
- --parse-script=file soubor se skriptem v PHP 7.3 pro analýzu zdrojového kódu v IPPcode19 
(chybí-li tento parametr, tak implicitní hodnotou je parse.php uložený v aktuálním adresáři);
- --int-script=file - soubor se skriptem v Python 3.6 pro interpret XML reprezentace kódu v IPPcode19 
(chybí-li tento parametr, tak implicitní hodnotou je interpret.py uložený v aktuálním adresáři);
- --parse-only - bude testován pouze skript pro analýzu zdrojového kódu v IPPcode19 (tento parametr se nesmí 
kombinovat s parametrem --int-script);
- --int-only - bude testován pouze skript pro interpret XML reprezentace kódu v IPPcode19
(tento parametr se nesmí kombinovat s parametrem --parse-script). Každý test je tvořen až 4 soubory 
stejného jména s příponami `src,in,out,rc` (ve stejném adresáři). Soubor s příponou `src` obsahuje zdrojový kód v 
jazyce IPPcode19. Soubory s příponami `in,out,rc` obsahují vstup a očekávaný/referenční výstup interpretace a 
očekávaný první chybový návratový kód analýzy a interpretace nebo bezchybový návratový kód 0. 
Pokud soubor s příponou `in` nebo `out` chybí, tak se automaticky dogeneruje prázdný soubor. V případě chybějícího 
souboru s příponou `rc` se vygeneruje soubor obsahující návratovou hodnotu 0. Testy budou umístěny 
v adresáři včetně případných podadresářů pro lepší kategorizaci testů. Adresářová struktura může mít libovolné 
zanoření. Není třeba uvažovat symbolické odkazy apod. 

##### Požadavky na výstupní HTML verze 5:
Přehledová stránka o úspěšnosti/neúspěšnosti jednotlivých testů a celých adresářů bude prohlédnuta ručně opravujícím, 
takže bude hodnocena její přehlednost a intuitivnost. Mělo by být na první pohled zřejmé, které testy uspěly a které 
nikoli, a zda případně uspěly všechny testy (případně i po jednotlivých adresářích). Výsledná stránka nesmí načítat 
externí zdroje a musí být možné ji zobrazit v běžném prohlížeči.
