<?php
require '../init_without_validate.php';

$pagetitle = "ASCIIsvg Reference";
$ispublic = true;
require '../header.php';

?>
<style>
table {
    border-collapse: collapse;
    vertical-align: top;
    display: inline-block;
    margin-right: 1em;
    margin-bottom: 2em;
}

table td,
table th {
    padding: 0.2em 0.4em;
    border: 1px solid rgb(200, 200, 200);
}
</style>
<h1>AasciiMath Reference</h1>
<h2>Syntax</h2>

	<p>Most AsciiMath symbols attempt to mimic in text what they look like 
	   rendered, like <code>oo</code> for `oo`.  Many symbols can also be
	   displayed using a TeX alternative, but a preceeding backslash is not 
	   required.</p>
       <br/>
<table>
	<caption>Operation symbols</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>+</td>
		<td></td>
		<td>`+`</td>
	</tr>
	<tr>
		<td>-</td>
		<td></td>
		<td>`-`</td>
	</tr>
	<tr>
		<td>*</td>
		<td>cdot</td>
		<td>`*`</td>
	</tr>
	<tr>
		<td>**</td>
		<td>ast</td>
		<td>`**`</td>
	</tr>
	<tr>
		<td>***</td>
		<td>star</td>
		<td>`***`</td>
	</tr>
	<tr>
		<td>//</td>
		<td></td>
		<td>`//`</td>
	</tr>
	<tr>
		<td>\\</td>
		<td>backslash<br/>setminus</td>
		<td>`\\`</td>
	</tr>
	<tr>
		<td>xx</td>
		<td>times</td>
		<td>`xx`</td>
	</tr>
	<tr>
		<td>-:</td>
		<td>div</td>
		<td>`-:`</td>
	</tr>
	<tr>
		<td>|&gt;&lt;</td>
		<td>ltimes</td>
		<td>`|&gt;&lt;`</td>
	</tr>
	<tr>
		<td>&gt;&lt;|</td>
		<td>rtimes</td>
		<td>`&gt;&lt;|`</td>
	</tr>
	<tr>
		<td>|&gt;&lt;|</td>
		<td>bowtie</td>
		<td>`|&gt;&lt;|`</td>
	</tr>
	
	<tr>
		<td>@</td>
		<td>circ</td>
		<td>`@`</td>
	</tr>
	<tr>
		<td>o+</td>
		<td>oplus</td>
		<td>`o+`</td>
	</tr>
    <tr>
		<td>o-</td>
		<td>ominus</td>
		<td>`o-`</td>
	</tr>
	<tr>
		<td>ox</td>
		<td>otimes</td>
		<td>`ox`</td>
	</tr>
	<tr>
		<td>o.</td>
		<td>odot</td>
		<td>`o.`</td>
	</tr>
	<tr>
		<td>sum</td>
		<td></td>
		<td>`sum`</td>
	</tr>
	<tr>
		<td>prod</td>
		<td></td>
		<td>`prod`</td>
	</tr>
	<tr>
		<td>^^</td>
		<td>wedge</td>
		<td>`^^`</td>
	</tr>
	<tr>
		<td>^^^</td>
		<td>bigwedge</td>
		<td>`^^^`</td>
	</tr>
	<tr>
		<td>vv</td>
		<td>vee</td>
		<td>`vv`</td>
	</tr>
	<tr>
		<td>vvv</td>
		<td>bigvee</td>
		<td>`vvv`</td>
	</tr>
	<tr>
		<td>nn</td>
		<td>cap</td>
		<td>`nn`</td>
	</tr>
	<tr>
		<td>nnn</td>
		<td>bigcap</td>
		<td>`nnn`</td>
	</tr>
	<tr>
		<td>uu</td>
		<td>cup</td>
		<td>`uu`</td>
	</tr>
	<tr>
		<td>uuu</td>
		<td>bigcup</td>
		<td>`uuu`</td>
	</tr>
	</tbody>
</table>

<table>
	<caption>Miscellaneous symbols</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>2/3</td>
		<td>frac{2}{3}</td>
		<td>`2/3`</td>
	</tr>
	<tr>
		<td>2^3</td>
		<td></td>
		<td>`2^3`</td>
	</tr>
	<tr>
		<td>sqrt x</td>
		<td></td>
		<td>`sqrt x`</td>
	</tr>

	<tr>
		<td>root(3)(x)</td>
		<td></td>
		<td>`root(3)(x)`</td>
	</tr>
	<tr>
		<td>int</td>
		<td></td>
		<td>`int`</td>
	</tr>
    <tr>
		<td>iint</td>
		<td></td>
		<td>`iint`</td>
	</tr>
	<tr>
		<td>oint</td>
		<td></td>
		<td>`oint`</td>
	</tr>
	<tr>
		<td>del</td>
		<td>partial</td>
		<td>`del`</td>
	</tr>
	<tr>
		<td>grad</td>
		<td>nabla</td>
		<td>`grad`</td>
	</tr>
    <tr>
		<td>^@</td>
		<td></td>
		<td>`^@`</td>
	</tr>
	<tr>
		<td>+-</td>
		<td>pm</td>
		<td>`+-`</td>
	</tr>
	<tr>
		<td>O/</td>
		<td>emptyset</td>
		<td>`O/`</td>
	</tr>
	<tr>
		<td>oo</td>
		<td>infty</td>
		<td>`oo`</td>
	</tr>
	<tr>
		<td>aleph</td>
		<td></td>
		<td>`aleph`</td>
	</tr>
	
	<tr>
		<td>:.</td>
		<td>therefore</td>
		<td>`:.`</td>
	</tr>
	<tr>
		<td>:'</td>
		<td>because</td>
		<td>`:'`</td>
	</tr>
	<tr>
		<td>|...|</td>
		<td>|ldots|</td>
		<td>`|...|`</td>
	</tr>
	<tr>
		<td>|cdots|</td>
		<td></td>
		<td>`|cdots|`</td>
	</tr>
	<tr>
		<td>vdots</td>
		<td></td>
		<td>`vdots`</td>
	</tr>
	<tr>
		<td>ddots</td>
		<td></td>
		<td>`ddots`</td>
	</tr>
	<tr>
		<td>a\ b</td>
		<td></td>
		<td>`a\ b`</td>
	</tr>
    <tr>
		<td>a thinspace b</td>
		<td></td>
		<td>`athinspaceb`</td>
	</tr>
	<tr>
		<td>a quad b</td>
		<td></td>
		<td>`aquadb`</td>
	</tr>
	<tr>
		<td>/_</td>
		<td>angle</td>
		<td>`/_`</td>
	</tr>
	<tr>
		<td>frown</td>
		<td></td>
		<td>`frown`</td>
	</tr>
	<tr>
		<td>/_\</td>
		<td>triangle</td>
		<td>`/_\\`</td>
	</tr>
	<tr>
		<td>diamond</td>
		<td></td>
		<td>`diamond`</td>
	</tr>
	<tr>
		<td>square</td>
		<td></td>
		<td>`square`</td>
	</tr>
	<tr>
		<td>|__</td>
		<td>lfloor</td>
		<td>`|__`</td>
	</tr>
	<tr>
		<td>__|</td>
		<td>rfloor</td>
		<td>`__|`</td>
	</tr>
	<tr>
		<td>|~</td>
		<td>lceiling</td>
		<td>`|~`</td>
	</tr>
	<tr>
		<td>~|</td>
		<td>rceiling</td>
		<td>`~|`</td>
	</tr>
	<tr>
		<td>CC</td>
		<td></td>
		<td>`CC`</td>
	</tr>
	<tr>
		<td>NN</td>
		<td></td>
		<td>`NN`</td>
	</tr>
	<tr>
		<td>QQ</td>
		<td></td>
		<td>`QQ`</td>
	</tr>
	<tr>
		<td>RR</td>
		<td></td>
		<td>`RR`</td>
	</tr>
	<tr>
		<td>ZZ</td>
		<td></td>
		<td>`ZZ`</td>
	</tr>
	<tr>
		<td>&quot;hi&quot;</td>
		<td>text(hi)</td>
		<td>`&quot;hi&quot;`</td>
	</tr>
	</tbody>
</table>

<table>
	<caption>Relation symbols</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>=</td>
		<td></td>
		<td>`=`</td>
	</tr>
	<tr>
		<td>!=</td>
		<td>ne</td>
		<td>`!=`</td>
	</tr>
	<tr>
		<td>&lt;</td>
		<td>lt</td>
		<td>`&lt;`</td>
	</tr>
	<tr>
		<td>&gt;</td>
		<td>gt</td>
		<td>`&gt;`</td>
	</tr>
	<tr>
		<td>&lt;=</td>
		<td>le</td>
		<td>`&lt;=`</td>
	</tr>
	<tr>
		<td>&gt;=</td>
		<td>ge</td>
		<td>`&gt;=`</td>
	</tr>
	<tr>
		<td>mlt</td>
		<td>ll</td>
		<td>`mlt`</td>
	</tr>
	<tr>
		<td>mgt</td>
		<td>gg</td>
		<td>`mgt`</td>
	</tr>
	<tr>
		<td>-&lt;</td>
		<td>prec</td>
		<td>`-&lt;`</td>
	</tr>
	<tr>
		<td>-&lt;=</td>
		<td>preceq</td>
		<td>`-&lt;=`</td>
	</tr>
	<tr>
		<td>&gt;-</td>
		<td>succ</td>
		<td>`&gt;-`</td>
	</tr>
	<tr>
		<td>&gt;-=</td>
		<td>succeq</td>
		<td>`&gt;-=`</td>
	</tr>
	<tr>
		<td>in</td>
		<td></td>
		<td>`in`</td>
	</tr>
	<tr>
		<td>!in</td>
		<td>notin</td>
		<td>`!in`</td>
	</tr>
	<tr>
		<td>sub</td>
		<td>subset</td>
		<td>`sub`</td>
	</tr>
	<tr>
		<td>sup</td>
		<td>supset</td>
		<td>`sup`</td>
	</tr>
	<tr>
		<td>sube</td>
		<td>subseteq</td>
		<td>`sube`</td>
	</tr>
	<tr>
		<td>supe</td>
		<td>supseteq</td>
		<td>`supe`</td>
	</tr>
	<tr>
		<td>-=</td>
		<td>equiv</td>
		<td>`-=`</td>
	</tr>
	<tr>
		<td>~=</td>
		<td>cong</td>
		<td>`~=`</td>
	</tr>
	<tr>
		<td>~~</td>
		<td>approx</td>
		<td>`~~`</td>
	</tr>
	<tr>
		<td>prop</td>
		<td>propto</td>
		<td>`prop`</td>
	</tr>
	</tbody>
</table>

<table>
	<caption>Logical symbols</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>and</td>
		<td></td>
		<td>`and`</td>
	</tr>
	<tr>
		<td>or</td>
		<td></td>
		<td>`or`</td>
	</tr>
	<tr>
		<td>not</td>
		<td>neg</td>
		<td>`not`</td>
	</tr>
	<tr>
		<td>=&gt;</td>
		<td>implies</td>
		<td>`=&gt;`</td>
	</tr>
	<tr>
		<td>if</td>
		<td></td>
		<td>`if`</td>
	</tr>
	<tr>
		<td>&lt;=&gt;</td>
		<td>iff</td>
		<td>`iff`</td>
	</tr>
	<tr>
		<td>AA</td>
		<td>forall</td>
		<td>`AA`</td>
	</tr>
	<tr>
		<td>EE</td>
		<td>exists</td>	
		<td>`EE`</td>
	</tr>
	<tr>
		<td>_|_</td>
		<td>bot</td>
		<td>`_|_`</td>
	</tr>
	<tr>
		<td>TT</td>
		<td>top</td>
		<td>`TT`</td>
	</tr>
	<tr>
		<td>|--</td>
		<td>vdash</td>
		<td>`|--`</td>
	</tr>
	<tr>
		<td>|==</td>
		<td>models</td>
		<td>`|==`</td>
	</tr>
	</tbody>
</table>

<table>
	<caption>Grouping brackets</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>(</td>
		<td></td>
		<td>`(`</td>
	</tr>
	<tr>
		<td>)</td>
		<td></td>
		<td>`)`</td>
	</tr>
	<tr>
		<td>[</td>
		<td></td>
		<td>`[`</td>
	</tr>
	<tr>
		<td>]</td>
		<td></td>
		<td>`]`</td>
	</tr>
	<tr>
		<td>{</td>
		<td></td>
		<td>`{`</td>
	</tr>
	<tr>
		<td>}</td>
		<td></td>
		<td>`}`</td>
	</tr>
	<tr>
		<td>(:</td>
		<td>langle</td>
		<td>`(:`</td>
	</tr>
	<tr>
		<td>:)</td>
		<td>rangle</td>
		<td>`:)`</td>
	</tr>
	<tr>
		<td>&lt;&lt;</td>
		<td></td>
		<td>`&lt;&lt;`</td>
	</tr>
	<tr>
		<td>&gt;&gt;</td>
		<td></td>
		<td>`&gt;&gt;`</td>
	</tr> 
	<tr>
		<td>{: x )</td>
		<td></td>
		<td>`{: x )`</td>
	</tr>
	<tr>
		<td>( x :}</td>
		<td></td>
		<td>`( x :}`</td>
	</tr>    
	<tr>
		<td>abs(x)</td>
		<td></td>
		<td>`abs(x)`</td>
	</tr>
	<tr>
		<td>floor(x)</td>
		<td></td>
		<td>`floor(x)`</td>
	</tr>
	<tr>
		<td>ceil(x)</td>
		<td></td>
		<td>`ceil(x)`</td>
	</tr>
	<tr>
		<td>norm(vecx)</td>
		<td></td>
		<td>`norm(vecx)`</td>
	</tr>
	</tbody>
</table>

<table>
	<caption>Arrows</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>uarr</td>
		<td>uparrow</td>
		<td>`uarr`</td>
	</tr>
	<tr>
		<td>darr</td>
		<td>downarrow</td>
		<td>`darr`</td>
	</tr>
	<tr>
		<td>rarr</td>
		<td>rightarrow</td>
		<td>`rarr`</td>
	</tr>
	<tr>
		<td>-&gt;</td>
		<td>to</td>
		<td>`-&gt;`</td>
	</tr>
	<tr>
		<td>&gt;-&gt;</td>
		<td>rightarrowtail</td>
		<td>`&gt;-&gt;`</td>
	</tr>
	<tr>
		<td>-&gt;&gt;</td>
		<td>twoheadrightarrow</td>
		<td>`-&gt;&gt;`</td>
	</tr>
	<tr>
		<td>&gt;-&gt;&gt;</td>
		<td>twoheadrightarrowtail</td>
		<td>`&gt;-&gt;&gt;`</td>
	</tr>
	<tr>
		<td>|-&gt;</td>
		<td>mapsto</td>
		<td>`|-&gt;`</td>
	</tr>
	<tr>
		<td>larr</td>
		<td>leftarrow</td>
		<td>`larr`</td>
	</tr>
	<tr>
		<td>harr</td>
		<td>leftrightarrow</td>
		<td>`harr`</td>
	</tr>
	<tr>
		<td>rArr</td>
		<td>Rightarrow</td>
		<td>`rArr`</td>
	</tr>
	<tr>
		<td>lArr</td>
		<td>Leftarrow</td>
		<td>`lArr`</td>
	</tr>
	<tr>
		<td>hArr</td>
		<td>Leftrightarrow</td>
		<td>`hArr`</td>
	</tr>
    <tr>
        <td></td>
        <td>rightleftharpoons</td>
        <td>`rightleftharpoons`</td>
    </tr>
	</tbody>
</table>

<table>
	<caption>Accents</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>hat x</td>
		<td></td>
		<td>`hat x`</td>
	</tr>
	<tr>
		<td>bar x</td>
		<td>overline x</td>
		<td>`bar x`</td>
	</tr>
	<tr>
		<td>ul x</td>
		<td>underline x</td>
		<td>`ul x`</td>
	</tr>
	<tr>
		<td>vec x</td>
		<td></td>
		<td>`vec x`</td>
	</tr>
	<tr>
		<td>tilde x</td>
		<td></td>
		<td>`tilde x`</td>
	</tr>
	<tr>
		<td>dot x</td>
		<td></td>
		<td>`dot x`</td>
	</tr>
	<tr>
		<td>ddot x</td>
		<td></td>
		<td>`ddot x`</td>
	</tr>
	<tr>
		<td>overset(x)(=)</td>
		<td>overset(x)(=)</td>
		<td>`overset(x)(=)`</td>
	</tr>
	<tr>
		<td>underset(x)(=)</td>
		<td></td>
		<td>`underset(x)(=)`</td>
	</tr>
	<tr>
		<td>ubrace(1+2)</td>
		<td>underbrace(1+2)</td>
		<td>`ubrace(1+2)`</td>
	</tr>
	<tr>
		<td>obrace(1+2)</td>
		<td>overbrace(1+2)</td>
		<td>`obrace(1+2)`</td>
	</tr>
	<tr>
		<td>overarc(AB)</td>
		<td>overparen(AB)</td>
		<td>`overarc(AB)`</td>
	</tr>
	<tr>
		<td>color(red)(x)</td>
		<td></td>
		<td>`color(red)(x)`</td>
	</tr>
	<tr>
		<td>cancel(x)</td>
		<td></td>
		<td>`cancel(x)`</td>
	</tr>
	</tbody>
</table>

<table>
	<caption>Greek Letters</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>See</th>
		<th>Type</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>alpha</td>
		<td>`alpha`</td>
	</tr>
	<tr>
		<td>beta</td>
		<td>`beta`</td>
	</tr>
	<tr>
		<td>gamma</td>
		<td>`gamma`</td>
		<td>Gamma</td>
		<td>`Gamma`</td>
	</tr>
	<tr>
		<td>delta</td>
		<td>`delta`</td>
		<td>Delta</td>
		<td>`Delta`</td>
	</tr>
	<tr>
		<td>epsilon</td>
		<td>`epsilon`</td>
	</tr>
	<tr>
		<td>varepsilon</td>
		<td>`varepsilon`</td>
	</tr>
	<tr>
		<td>zeta</td>
		<td>`zeta`</td>
	</tr>
	<tr>
		<td>eta</td>
		<td>`eta`</td>
	</tr>
	<tr>
		<td>theta</td>
		<td>`theta`</td>
		<td>Theta</td>
		<td>`Theta`</td>
	</tr>
	<tr>
		<td>vartheta</td>
		<td>`vartheta`</td>
	</tr>
	<tr>
		<td>iota</td>
		<td>`iota`</td>
	</tr>
	<tr>
		<td>kappa</td>
		<td>`kappa`</td>
	</tr>
	<tr>
		<td>lambda</td>
		<td>`lambda`</td>
		<td>Lambda</td>
		<td>`Lambda`</td>
	</tr>
	<tr>
		<td>mu</td>
		<td>`mu`</td>
	</tr>
	<tr>
		<td>nu</td>
		<td>`nu`</td>
	</tr>
	<tr>
		<td>xi</td>
		<td>`xi`</td>
		<td>Xi</td>
		<td>`Xi`</td>
	</tr>
	<tr>
		<td>pi</td>
		<td>`pi`</td>
		<td>Pi</td>
		<td>`Pi`</td>
	</tr>
	<tr>
		<td>rho</td>
		<td>`rho`</td>
	</tr>
	<tr>
		<td>sigma</td>
		<td>`sigma`</td>
		<td>Sigma</td>
		<td>`Sigma`</td>
	</tr>
	<tr>
		<td>tau</td>
		<td>`tau`</td>
	</tr>
	
	<tr>
		<td>upsilon</td>
		<td>`upsilon`</td>
	</tr>
	<tr>
		<td>phi</td>
		<td>`phi`</td>
		<td>Phi</td>
		<td>`Phi`</td>
	</tr>
	<tr>
		<td>varphi</td>
		<td>`varphi`</td>
	</tr>
	<tr>
		<td>chi</td>
		<td>`chi`</td>
	</tr>
	<tr>
		<td>psi</td>
		<td>`psi`</td>
		<td>Psi</td>
		<td>`Psi`</td>
	</tr>
	<tr>
		<td>omega</td>
		<td>`omega`</td>
		<td>Omega</td>
		<td>`Omega`</td>
	</tr>

	</tbody>
</table>

<table>
	<caption>Font commands</caption>
	<thead>
	<tr>
		<th>Type</th>
		<th>TeX alt</th>
		<th>See</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>bb "AaBbCc"</td>
		<td>mathbf "AaBbCc"</td>
		<td>`bb "AaBbCc"`</td>
	</tr>
	<tr>
		<td>bbb "AaBbCc"</td>
		<td>mathbb "AaBbCc"</td>
		<td>`bbb "AaBbCc"`</td>
	</tr>
	<tr>
		<td>cc "AaBbCc"</td>
		<td>mathcal "AaBbCc"</td>
		<td>`cc "AaBbCc"`</td>
	</tr>
	<tr>
		<td>tt "AaBbCc"</td>
		<td>mathtt "AaBbCc"</td>
		<td>`tt "AaBbCc"`</td>
	</tr>
	<tr>
		<td>fr "AaBbCc"</td>
		<td>mathfrak "AaBbCc"</td>
		<td>`fr "AaBbCc"`</td>
	</tr>
	<tr>
		<td>sf "AaBbCc"</td>
		<td>mathsf "AaBbCc"</td>
		<td>`sf "AaBbCc"`</td>
	</tr>
	</tbody>
</table>
<br/>

<h3>Standard Functions</h3>

<p>sin, cos, tan, sec, csc, cot, 
arcsin, arccos, arctan, sinh, cosh, tanh, sech, csch, coth, exp, log, ln,
det, dim, mod, gcd, lcm, lub, glb, min, max, f, g.</p>

<br/>

<h3>Special Cases</h3>

<p>Matrices: <code>[[a,b],[c,d]]</code> yields to `[[a,b],[c,d]]`</p>

<p>Column vectors: <code>((a),(b))</code> yields to `((a),(b))`</p>

<p>Augmented matrices: <code>[[a,b,|,c],[d,e,|,f]]</code> yields to `[[a,b,|,c],[d,e,|,f]]`</p>

<p>Matrices can be used for layout:
 <code>{(2x,+,17y,=,23),(x,-,y,=,5):}</code> yields
 `{(2x,+,17y,=,23),(x,-,y,=,5):}`</p>
 
<p>Complex subscripts: <code>lim_(N->oo) sum_(i=0)^N</code> yields to `lim_(N->oo) sum_(i=0)^N`</p>

<p>Subscripts must come before superscripts:
	<code>int_0^1 f(x)dx</code> yields to `int_0^1 f(x)dx`</p>
	
<p>Derivatives: <code> f'(x) = dy/dx</code> yields `f'(x) = dy/dx`<br/>
	For variables other than x,y,z, or t you will need grouping symbols: 
	<code> (dq)/(dp)</code> for `(dq)/(dp)`</p>

<p>Overbraces and underbraces:
	<code>ubrace(1+2+3+4)_("4 terms")</code> yields `ubrace(1+2+3+4)_("4 terms")`.<br/>
	<code>obrace(1+2+3+4)^("4 terms")</code> yields `obrace(1+2+3+4)^("4 terms")`.
</p>

<p>Attention: Always try to surround the <code>&gt;</code> and
	<code>&lt;</code> characters with spaces so that the html parser does not
	confuse it with an opening or closing tag!</p>
<br/>

<?php

require '../footer.php';