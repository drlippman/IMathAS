// IMathAS keyword / macro / variable lists.
// Extracted verbatim from the CodeMirror 5 imathas.js mode; keep in sync with the macro libraries.
function keywords(str) {
  var obj = {}, words = str.split(" ");
  for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
  return obj;
}

  var imathasKeywords = "and array as break case catch continue do else elseif " +
    "for foreach if or off switch try while where empty isset list";
  var imathasAtoms = "true false null TRUE FALSE NULL";
  //macros/array.php
  var imathasBuiltin = "listtoarray arraytolist calclisttoarray sortarray consecutive calconarray mergearrays sumarray"
        + " diffarrays intersectarrays joinarray unionarrays calconarrayif subarray arrayfindindex fillarray"
        + " arraysetvalues arrayfindindices stringtoarray arrayhasduplicates are_numeric multicalconarray"
        + " jointsort ABarray keepif splicearray";
  //macros/conditional.php
  imathasBuiltin += " comparenumbers comparefunctions scoremultiorder scoreperiodic scorestring comparelogic comparesetexp"
        + " comparentuples comparenumberswithunits comparesameform comparecomplex compareintervals";
  //macros/feedback.php
  imathasBuiltin += " getfeedbackbasic getfeedbacktxt getfeedbacktxtessay getfeedbacktxtnumber getfeedbacktxtnumfunc"
        + " getfeedbacktxtcalculated";
  //macros/format.php
  imathasBuiltin += " makepretty makeprettydisp dispreducedfraction polymakepretty polymakeprettydisp makexpretty"
        + " makexprettydisp prettyint prettyreal prettysigfig roundsigfig prettynegs numtowords prettytime"
        + " makereducedfraction makereducedmixednumber makescinot today numtoroman makexxpretty makexxprettydisp"
        + " htmldisp prettysmallnumber makeprettynegative fractowords formatcomplex prettysigfig_instring"
        + " prettyreal_instring round_instring simplifyexpr";
  //macros/graph.php
  imathasBuiltin += " showplot addlabel showasciisvg arraystodots arraystodoteqns replacealttext changeimagesize"
        + " textonimage addplotborder addlabelabs addfractionaxislabels connectthedots adddrawcommand mergeplots"
        + " invertplot addimageborder";
  //macros/helpers.php
  imathasBuiltin += " getsnapwidthheight makenumberrequiretimes getstuans checkreqtimes stringtopolyterms"
        + " gettwopointlinedata getdotsdata getntupleparts getopendotsdata gettwopointdata gettwopointformulas"
        + " getlinesdata getineqdata sorttwopointdata stuansready checkanswerformat getsigfigs checksigfigs"
        + " setupmathquillfillin";
  //macros/html.php
  imathasBuiltin += " formhoverover formpopup forminlinebutton";
  //macros/math.php
  imathasBuiltin += " gcd lcm mod definefunc evalfunc evalnumstr normalizefunc decimaltofraction ifthen cases"
        + " getnumbervalue is_nicenumber";
  //macros/randomizers.php
  imathasBuiltin += " rrand rands rrands randfrom randsfrom jointrandfrom diffrandsfrom nonzerorand nonzerorrand"
        + " nonzerorands nonzerorrands diffrands diffrrands nonzerodiffrands nonzerodiffrrands singleshuffle"
        + " jointshuffle randpythag randname randnamewpronouns randmalename randfemalename randnames"
        + " randmalenames randfemalenames randcity randcities randstate randstates randcountry randcountries";
  //macros/string.php
  imathasBuiltin += " stringappend stringprepend stringpos stringlen stringclean";
  //macros/table.php
  imathasBuiltin += " showarrays horizshowarrays showdataarray addcopytablebutton showrecttable";
  //other: mathphp2.php, mathparser.php, and PHP builtins
  imathasBuiltin += " exp nthlog sinn cosn tann secn cscn cotn rand is_array count in_array array_flip array_reverse root"
        + " is_numeric is_nan sign sgn dechex hexdec print_r fmod safepow strtoupper strtolower ucfirst sprintf"
        + " htmlentities substr substr_count str_replace explode array_unique rawurlencode array_keys"
        + " array_values isset atan2 preg_match preg_replace intval floatval levenshtein uniqid trim rtrim ltrim"
        + " is_string";
  //builtin
  imathasBuiltin += " loadlibrary importcodefrom includecodefrom setseed";
  //math
  imathasBuiltin += " sin cos tan sec csc cot sinh cosh tanh sech csch coth arcsin arccos arctan arcsec arccsc arccot arcsinh arccosh arctanh arcsech arccsch arccoth atan2 sqrt ceil floor round log ln abs max min count";
  //libs
    // acct
    imathasBuiltin += " makejournal scorejournal makeaccttable makeaccttable2 makeaccttable3 makeTchart scoreTchart makestatement scorestatement makeinventory scoreinventory makeTchartsfromjournal scoreTchartsfromjournal makeledgerfromjournal maketrialbalance maketrialbalancefromjournal scoretrialbalance scoretrialbalancefromjournal totalsfromjournal prettyacct";
    // biology
    imathasBuiltin += " bio_getcodonname bio_randcodon bio_anticodon bio_translation bio_splitcodons";
    // calculus
    imathasBuiltin += " calculusdiffquotient calculusnumint";
    // chemistry
    imathasBuiltin += " chem_disp chem_mathdisp chem_isotopedisp chem_getsymbol chem_getnumber chem_getname chem_getweight chem_getmeltingpoint chem_getboilingpoint chem_getfamily chem_randelementbyfamily chem_diffrandelementsbyfamily chem_getrandcompound chem_getdiffrandcompounds chem_decomposecompound chem_getcompoundmolmass chem_randanion chem_randcation chem_makeioniccompound chem_getsolubility chem_eqndisp chem_balancereaction chem_vsepr";
    // chgbase
    imathasBuiltin += " baseconvert asciitodec dectoascii draw_mayan_number draw_babylonian_number decbin bindec bitwise_xor";
    // complex
    imathasBuiltin += " cx_add cx_arg cx_conj cx_cubicRoot cx_div cx_format2pol cx_format2std cx_matrixreduce cx_modul cx_mul cx_quadRoot cx_prettyquadRoot cx_plot cx_pow cx_polEu cx_pol2std cx_root cx_std2pol cx_sub";
    // construct2
    imathasBuiltin += " addConstruct2";
    // crypto
    imathasBuiltin += " randfiveletterword shiftcipher transcipher randmilphrase chunktext modularexponent cryptorsakeys randsubmap subcipher";
    // dates
    imathasBuiltin += " dates_adddays dates_addweeks dates_addmonths dates_addyears dates_eomonth dates_bomonth dates_diffdays dates_randdate dates_dateformat";
    // finance
    imathasBuiltin += " futureValue presentValue payment numberOfPeriods interest vmTVM";
    // finance2
    imathasBuiltin += " fin_FV fin_PV fin_PMT fin_Nper fin_IY fin_IRR fin_NPV fin_iPMT fin_pPMT fin_DBD fin_TVM";
    // finderiv
    imathasBuiltin += " finderiv_payout finderiv_fwdprice finderiv_fwdpricediv finderiv_fwdcontract finderiv_bsm finderiv_immdate finderiv_equityfutdate finderiv_convertrate finderiv_fairforwardrate finderiv_fra finderiv_checkpayout";
    // fractions
    imathasBuiltin += " fractionrand fractiondiffdrands fractiondiffdrandsfrom fractionparse fractiontomixed fractiontodecimal fractionadd fractionsubtract fractionmultiply fractiondivide fractionreduce fractionneg fractionpower fractionroot";
    // fractions2
    imathasBuiltin += " diffrandfractions randfractions randfraction simplifyfraction dispsimplifyfraction fractionnumerator fractiondenominator isreducedfraction makerepeatingdecimal repeatingdecimal2fraction";
    // functioneval
    imathasBuiltin += " fe_lpfnc";
    // geogebra
    imathasBuiltin += " addGeogebra addGeogebraJava ggb_axes ggb_addobject ggb_addslider ggb_getparams";
    // graphtheory
    imathasBuiltin += " graphspringlayout graphcirclelayout graphgridlayout graphpathlayout graphcircleladder graphcircle graphbipartite graphgrid graphrandom graphrandomgridschedule graphemptygraph graphdijkstra graphbackflow graphkruskal graphadjacencytoincidence graphincidencetoadjacency graphdrawit graphdecreasingtimelist graphcriticaltimelist graphcircledstar graphcircledstarlayout graphmaketable graphsortededges graphcircuittoarray graphcircuittostringans graphnearestneighbor graphrepeatednearestneighbor graphgetedges graphgettotalcost graphnestedpolygons graphmakesymmetric graphisconnected graphgetedgesarray graphsequenceeuleredgedups graphsequenceishamiltonian graphshortestpath graphgetpathlength graphcomparecircuits graphlistprocessing graphscheduletaskinfo graphschedulecompletion graphscheduleidle graphdrawschedule graphschedulelayout graphscheduleproctasks graphschedulemultchoice graphprereqtable graphgetcriticalpath";
    // ineq
    imathasBuiltin += " ineqplot ineqbetweenplot";
    // interval_ext
    imathasBuiltin += " canonicInterval intersection";
    // interval
    imathasBuiltin += " linegraph linegraphbrackets forminterval intervalstodraw";
    // JSXG
    imathasBuiltin += " loadJSX JSXG_createAxes JSXG_createPolarAxes JSXG_addPolar JSXG_addFunction JSXG_addParametric JSXG_addText JSXG_addSlider JSXG_addTangent JSXG_setAttribute JSXG_createBlankBoard JSXG_addArrow JSXG_addPoint JSXG_addSegment JSXG_addLine JSXG_addRay JSXG_addAngle JSXG_addCircle JSXG_addPolygon JSXG_addGlider JSXG_getLabel";
    // jsxgraph
    imathasBuiltin += " jsxBoard jsxSlider jsxPoint jsxGlider jsxIntersection jsxFunction jsxParametric jsxPolar jsxText jsxCircle jsxLine jsxImage jsxSegment jsxRay jsxVector jsxAngle jsxPolygon jsxTangent jsxIntegral jsxRiemannSum jsx_getXCoord jsx_getYCoord jsx_getCoords jsxSuspendUpdate jsxUnsuspendUpdate jsxSetChild";
    // lineutil
    imathasBuiltin += " lineboundarycoord";
    // logistic
    imathasBuiltin += " logisticregression logisticpredict logisticsolve";
    // matrix
    imathasBuiltin += " matrix matrixformat matrixformatfrac matrixsystemdisp matrixsum matrixdiff matrixscalar matrixprod matrixaugment matrixrowscale matrixrowswap matrixrowcombine matrixrowcombine3 matrixidentity matrixtranspose matrixrandinvertible matrixrandunreduce matrixinverse matrixinversefrac matrixsolve matrixsolvefrac polyregression matrixgetentry matrixRandomSpan matrixNumberOfRows matrixNumberOfColumns matrixgetrow matrixgetcol matrixgetsubmatrix matrixdisplaytable matrixreduce matrixnumsolutions matrixround matrixFromCols matrixFromRows matrixCompare matrixGetRank arrayIsZeroVector matrixFormMatrixFromEigValEigVec matrixIsRowsLinInd matrixIsColsLinInd matrixIsEigVec matrixIsEigVal matrixGetRowSpace matrixGetColumnSpace matrixFromEigenvals matrixFormatEigenvecs matrixAxbHasSolution matrixAspansB matrixAbasisForB matrixGetMinor matrixDet matrixRandomMatrix matrixParseStuans";
    // normalcurve
    imathasBuiltin += " normalcurve normalcurve2 normalcurve3";
    // plot3d
    imathasBuiltin += " plot3d spacecurve replace3dalttext CalcPlot3Dembed CalcPlot3Dlink";
    // poly3
    imathasBuiltin += " formpoly3fromstring formpoly3fromresults dividepoly3 longdivisionpoly3 writepoly3";
    // polys2
    imathasBuiltin += " formpoly2 writepoly2 addpolys2 subtpolys2 multpolys2 scalepoly2 getcoef2 polypower2";
    // polys
    imathasBuiltin += " formpoly formpolyfromroots writepoly writepolyfrac addpolys subtpolys multpolys divpolys scalepoly roundpoly quadroot getcoef polypower checkpolypowerorder derivepoly polys_getdegree describepoly describerational";
    // primes
    imathasBuiltin += " getprime getprimes isprime";
    // radicals
    imathasBuiltin += " reduceradical reduceradicalfrac reduceradicalfrac2 reduceradicalfrac3 reducequadraticform";
    // shapes
    imathasBuiltin += " draw_angle draw_circle draw_circlesector draw_square draw_rectangle draw_triangle draw_polygon draw_prismcubes draw_cylinder draw_cone draw_sphere draw_pyramid draw_rectprism draw_polyomino";
    // simplex
    imathasBuiltin += " simplex simplexver simplexchecksolution simplexcreateanswerboxentrytable simplexcreateinequalities simplexconverttodecimals simplexconverttofraction simplexdebug simplexdefaultheaders simplexdisplaycolortable simplexdisplaylatex simplexdisplaylatex2 simplexdisplaytable2 simplexdisplaytable2string simplexfindpivotpoint simplexfindpivotpointmixed simplexgetentry simplexsetentry simplexpivot simplexreadtoanswerarray simplexreadsolution simplexreadsolutionarray simplexsolutiontolatex simplexsolve2 simplexnumberofsolutions simplexdisplaytable simplexsolve";
    // socchoice
    imathasBuiltin += " apportion apportion_info banzhafpower shapleyshubikpower";
    // solvers
    imathasBuiltin += " discretenewtons bisectionsolve";
    // stats
    imathasBuiltin += " nCr nPr mean stdev variance absmeandev percentile interppercentile Nplus1percentile quartile TIquartile Excelquartile Excelquartileexc Nplus1quartile allquartile median freqdist frequency histogram fdhistogram fdbargraph normrand expdistrand boxplot normalcdf tcdf invnormalcdf invtcdf invtcdf2 linreg expreg countif binomialpdf binomialcdf chicdf invchicdf chi2cdf invchi2cdf fcdf invfcdf piechart mosaicplot checklineagainstdata chi2teststat checkdrawnlineagainstdata csvdownloadlink modes forceonemode dotplot gamma_cdf gamma_inv beta_cdf beta_inv anova1way_f anova1way anova2way anova_table anova2way_f student_t cluster_bargraph stem_plot poissonpdf poissoncdf geometricdf geometripdf negbinomialpdf negbinomialcdf hypgeomcdf hypgeompdf";
    // timedate
    imathasBuiltin += " timetominutes thisyear";
    // units
    imathasBuiltin += " parseunits";
    // vector
    imathasBuiltin += " dotp crossp vecnorm vecsum vecdiff vecprod veccompareset veccomparesamespan";
    // virtmanip
    imathasBuiltin += " vmsetup vmgetlistener vmgetparam vmparamtoarray vmsetupchipmodel vmchipmodelgetcount vmsetupnumbertiles vmnumbertilesgetcount vmsetupitemsort vmitemsortgetcontainers vmsetupnumberlineaddition vmnumberlineadditiongetvals vmsetupnumberline vmnumberlinegetvals vmsetupnumberlineinterval vmnumberlineintervalgetvals vmsetupfractionline vmgetfractionlinevals vmsetupfractionmult vmgetfractionmultvals vmsetupfractioncompare vmgetfractioncompareval vmdrawinchruler vmdrawcmruler vmdrawclock";
    // diffeq 
    imathasBuiltin += " diffeq_slopefield";
  var imathasSpecialVars = keywords("$abstolerance $ansprompt $anstypes $answeights $answer $answerbox $answerboxsize $answerformat $answers $answersize $answertitle $background $displayformat $domain $formatfeedbackon $grid $hidepreview $hidetips $matchlist $noshuffle $partialcredit $partweights $previewloc $questions $questiontitle $reltolerance $reqdecimals $reqsigfigs $requiretimes $requiretimeslistpart $scoremethod $overridescore $showanswer $showanswerloc $snaptogrid $strflags $variables $defaults");
  var imathasDisallowedVars = keywords("$link $qidx $qnidx $seed $qdata $toevalqtxt $la $laarr $shanspt $GLOBALS $laparts $anstype $kidx $iidx $tips $optionsPack $partla $partnum $score $disallowedvar $allowedmacros $wherecount $countcnt $myrights $myspecialrights");

export {
  imathasKeywords, imathasAtoms, imathasBuiltin,
  imathasSpecialVars, imathasDisallowedVars, keywords
};
