
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>Diiscogs</title>
  <link rel="stylesheet" href="/styme.css"></link>
<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>-->
<script src="/lb/jquery-1.whatever.js"></script>
  <script>
    var body
    var content;
    var header;
    var input;
    var caption;

    var loadMoreWhenReady
    var loaded
    var clickedLink
    var prev
    var alreadyZoomed
    var zoomed
    var scrollStopped
    var moved=false;
    var qa
    var i1a
    var i2a
    var i3a
    var na
    var initScroll=new Object();
    var prevScroll= new Object();
    var zoomRatio=0.14;
    var ignoreScroll=false;
    var widthToWindowWidth=false;
    var mousePos=new Object();
    var inp=new Object();
    var inpL=new Object();


    var resultsPerPage=25;
    var urlParamStartIndex=window.location.href.indexOf('/', 7)+1;
    //request object
    var rq = new Object();
    //action,type,value,min,max
    var propNames=['a', 't', 'v', 'mn', 'mx'];
    var types={'a':'Artists', 'm':'Masters', 'l':'Labels', 'r':'Releases', 't':'Tracks'};
    var codes=['SQL','XML'];
    var chosen={'codes':null,'searchOn':'Artists','relatedForm':0}; 
    var pt=rq.t;

    var rP=window.innerWidth/window.innerHeight;
    var pfvd_ctx;
    var gn=Math.floor(window.innerHeight/275)+2;
    var cont
    function addBasicLayout() {
      body=$('body');
	header=$('<header></header>').appendTo(body);
	logo=$('<div id="logo"><a href="/p/0/0"><img src="/img/diiscogs.png"/></a></div>').appendTo(header);
	search=$('<div id="search"><input type="text" name="search" ><button type="submit">search</button></div>').appendTo(header);
	select=$('<div id="select"></div>').appendTo(search);
      content=$('<div id="content"></div>').appendTo(body);
    }
    $(document).ready(function(){
	addBasicLayout();
	input=$('input');
	display();
	console.log("j");
	var timeoutID = null;
	var stateTimeOut = null;

  	function updateSearchResults(str) {
		stateLink({a:'s',t:'a',v:str});
  	}
  	input.keyup(function(e) {
    		clearTimeout(timeoutID);
    		timeoutID = setTimeout(updateSearchResults.bind(undefined, e.target.value), 50);
    		//stateTimeOut = setTimeout(function(s){ rq={a:'s',t:'a',v:str}; puushState();}.bind(undefined, e.target.value), 500);
  	});

	$('button').on('click', function(event){
		stateLink({a:'s',t:'a',v:input.val()})	
});

	$(document).on('click', 'a', function(event){
		if(this.href.startsWith('http://'+document.domain) && !this.href.endsWith('/img/schema.png') && !this.href.endsWith('/dl/discogs.dtd')){
			event.preventDefault();
			event.stopPropagation();
			clickedLink=this;
			//console.log("merdouille")
			goToLink(this.href);
		}
        });
	//$(document).on('change','.choices',function(event){
	$(document).on('change','.choices',function(event){
		var id=$(this).attr("id");
		console.log($(this).attr("id"));
		var formID=$(this).parent().attr('id');
		/*if($(this).prop('checked')){
			$(this).prop('checked',false);
			chosen[formID]=null;
			updateChoices();
		}else{*/
			chosen[formID]=id;
			var options=$(this).parent().siblings('.choosed');
			var choice=options.filter('[id$='+id+']');
			console.log(options);
			console.log(choice);
			options.not(choice).hide();
			choice.show();
	//	}
	});
	
	/*$('input[type=radio]').on('change', function(event) {
		console.log('change');
		alert(this.val()); 
	});*/
	window.onpopstate=function(event) {
		console.log('popstate:');
		console.log(event.state);
		linkRestore(event.state);
	};
    
    })
function display(){
	if(rq.a == undefined){
                 var crtUrl=window.location.href;
                 switch(crtUrl.substring(urlParamStartIndex)){
			case 'index.php':
			case '/':
			case '':
                        	console.log('no urlParams');
                        	stateLink({a:'p', t:'0', v:'0'});
				break;
			default:
                        	goToLink(window.location.href);
		}
         }
}
function stateLink(i){
	 link(i);
	 puushState();
}
function goToLink(linkUrl){
	console.log("going to "+linkUrl);
        urlParamToInpObj(linkUrl);
	linkRestore(rq);
	 puushState();
}
function linkRestore(i){
	if(i.a == 's') {
		input.val(i.v);
	}
	link(i)
}
function link(i){
	rq=i;
	if(rq.a == 'd' || rq.a == 's' || rq.a == 'p' ) {
                //puushState();
		console.log('');
		clearContent();
		if(rq.a == 's'){
			if(rq.mn == undefined){
				rq.mn=0;
				rq.mx=resultsPerPage;
			}
		}
		if(rq.a == 'p'){
			if(rq.t == undefined || rq.t == ''){
				rq.t=0;
			}
			if(rq.v == undefined){
				rq.v=0;
			}
		}
	}
	
	if(rq.v != ''){
		getJson(rq);
	}
}
function urlParamToInpObj(fullUrl) {
	rq = {};
	var dat=decodeURI(fullUrl).substring(urlParamStartIndex).split("/");
	console.log(dat);
	var i, m
	for(i=0;i<5;i++){
		m=propNames[i]
		rq[m]=dat[i];
	}
}
function clearContent(){
	console.log('erasing page');
	content.children().remove();
}
function puushState(method) {
	console.log('pushingState:');
        console.log(rq);
	if(method == undefined){
		history.pushState(rq, '', makeUrl(rq));
	}
	else {
		history.replaceState(rq, '', makeUrl(rq));
	}
}
function makeUrl(i) {
	var url='';
	//console.log(i);
	for(var prop in i){
		//console.log(prop);
		//console.log(i[prop]);
		if(i[prop] != undefined) {
			url=url+'/'+i[prop];
		}	
	}
	console.log('url:'+url);
	return url;
}
function getJson(input){
        //console.log(input);
	
        $.ajax({
                url: '/jax.php',
                type: 'POST',
                dataType: 'json',
                data: input,
                success: function(data){
                        handleResponse(data);
                },
                error: function (){
                        alert('request failed');
                }
         });
}
function handleResponse(json) {
	//console.log(json);
	//should have been done in object oriented style by function overloading but meh, no time
	switch(rq.a) {
		case 'd':
			displayEntry(json,rq);
		//	console.log(json[0]);
			displayRelatedContent(json[0].related,rq);
			break;
		case 's':
			displaySearch(json,rq);break;
		case 'p':
			displayPres(json,rq);break;
	}
}
function displayPres(json,rq){
	if(rq.t > 0){
		pres=createTable('pres',content);
		if(json.length == 0){
			$('<tr><th>No results</th></tr>').appendTo(pres);
		}
		else {
			initializeTable(pres);
			json = displayCodes(json,pres,['SQL']);
			displayTable(json,pres);
		}
	}else{
		displayHomePage();
	}
}
function displayHomePage(){
	var json={
		'name': 'Requêtes',
		'sélection avec projection' : '<a href="/p/1/0">Les albums sortis en 1975 dont le titre commence par "Love"</a>', 
		'jointure' : '<a href="/p/2/0">Les albums de Funk sortis en 1975</a>', 
		"moyenne sur l'intégralité d'un attribut" : '<a href="/p/3/0">Le nombre moyen de piste par album (en fait par "publication" ou release)</a>', 
		'regroupement avec calcul' : "<a href='/p/4/0'>Le nombre d'albums sortis chaque année</a>", 
		'différence' : '<a href="/p/5/0">Les albums dans lesquels il y a du Vibraphone et du Saxophone mais pas de Trompette</a>', 
		'division' : '<a href="/p/6/0">Les artistes qui ont sorti un album tout les ans entre 1970 et 1990</a>' 
	};
	queries=createTable('queries',content);
	initializeTable(queries);
	var n = json.name;
	delete json.name;
	addToCaption(n,'').addClass('title');
	displayHorizontalTable(json,queries);
	var json={
		'name' : 'Projet DSB',
		'Auteur' : 'Robin Adili',
		'Données' : '258 058 931 n-uplets, 37 tables, disponibles en XML <a href="http://data.discogs.com/">ici</a>, le script PHP (pas de moi) pour les importer est disponible <a href="https://github.com/korcstar/php-discogsTomysql">ici</a>',
		'XML' : "Au vu de la taille, le XML est généré individuellement pour chaque page, on peut l'afficher en cliquant sur 'XML' en haut de la page",
		'DTD' : 'est disponible <a href="/dl/discogs.dtd">ici</a>',
		'Schéma conceptuel' : '<a target="_blank" href="http://'+document.domain+'/img/schema.png"><img src="/img/schema.png" height="100px"/></a>'
	};
	home=createTable('home',content);
	initializeTable(home);
	var n = json.name;
	delete json.name;
	addToCaption(n,'').addClass('title');
	displayHorizontalTable(json,home);
}
function handleEmptyAnswer(answer,whatToSay,wheretoSayIt){
	var t = answer.length == 0;
	return t && $('<tr><th>'+whatToSay+'</th></tr>').whereToSayIt && t;
}
function updateChoices(){
	console.log('updating choices');
	//last minute dirty trick
	if(pt != rq.t){
		pt=rq.t;
		chosen['relatedForm']=0;
	}
	$('form').each(function(i){
		var id=$(this).attr('id');
		console.log('for form: '+id);
		var c=chosen[id];
		options=$(this).siblings('.choosed');
		var buttons = $(this).children();
		if(c == null){
			//options.hide();
			buttons.last().click();
		}
		else if(Number.isInteger(c)){
			console.log(buttons[c]);
			buttons[c].click();
			/*var a = options[c];
			options.not(a).hide();
			a.show();*/
		}
		else{
			buttons.filter('#'+c).click();
		}
	});
}
function createTable(id,container){
	var a =$("<table id='"+id+"'>");
	container.prepend(a);
	return a;
}
function displayEntry(json,rq){
	entry=createTable('entry',content);
	if(!handleEmptyAnswer(json,'404',entry)){
		initializeTable(entry);
		json = displayCodes(json,entry,codes);
		if(rq.t == 'm'){
			var n = json.title;
			delete json.title;
		}else{
			var n = json.name;
			delete json.name;
		}
		addToCaption(n,'').addClass('title');
		displayHorizontalTable(json,entry);
	}
}
function displayCodes(json,tableElement,codeNames){
	console.log(codeNames);
	codeNames.forEach(function(c) {json=displayCode(json,tableElement,c);});
	displayChoicesForm(codeNames,caption,'codes');
	return json[0];
}
function displayCode(json,tableElement,id){
	//console.log(json);
	var code=addToCaption(json.shift().map(function(a){return '<div>'+a+'</div>';}).join(''),'c'+id);
	code.attr('class','code choosed');
	//code.insertBefore(i);
	return json;
	
}
function displayRelatedContent(json,rq){
	//return '';
	related=$('<div>').attr('id','related').appendTo(content);
	console.log(json);
	if(!handleEmptyAnswer(json,'404',related)){
		Object.keys(json).forEach(function(k){dipslayRelatedContentTable(k,json[k],related) });
		displayChoicesForm(Object.keys(json),related,'relatedForm');
		//related.children().first().
	}
}
function displayChoicesForm(keys,container,id){
	var k=Array.from(keys);
	k.push(id+'none');
	var ab=$(k.map(function(key){ 
		var b=key.replace(/ /g,'_');
		var i=$('<input>').attr('class','choices').attr('id',b).attr('name',id).attr('type','radio');
		var j=$('<label>').attr('for',b).append(b);
		return $('<div>').append(i).append(j).html();
	}).join('<span> | </span>'))
	ab.filter('label').last().empty().append('none')
	//console.log(ab.html());
	var y =$('<form>').attr('id',id).append(ab).prependTo(container);
	updateChoices();
}
function dipslayRelatedContentTable(key,values,container){
	var b=key.replace(/ /g,'_');
	var a=createTable('c'+b,container).addClass('choosed');
	initializeTable(a);
	addToCaption(b).addClass('title');
	displayTable(values,a);
}
function displayHorizontalTable(json,tableElement){
	//console.log(json);
	tableElement.addClass('horTable');
	Object.keys(json).forEach(function(k){ 
		if(!fieldIsEmpty(json[k]) && k !== 'related'){
			displayHorizontalRow(k,json[k],tbody);
		} 
	});
}
function fieldIsEmpty(value){
	return (Array.isArray(value) && value.length == 0) || value == null;
}
function displayHorizontalRow(name,v,container){
	if(name.indexOf('ID') != -1){
		return '';
	}
	var tr=$('<tr>').appendTo(container);
	if(Array.isArray(v)){
		v=makeHyperLinkedFields(v);
	}
	/*else{
		if(v.indexOf('http://') != -1){
			v=v.split(',').map(function(e){ return '<a href="'+e+'">'+e+'</a>';}).join('<span>, </span>')
		}
	}*/
	$('<th>'+name+'</th><td>'+v+'</td>').appendTo(tr);
}
function getVideoId(url) {
    var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    var match = url.match(regExp);

    if (match && match[2].length == 11) {
        return match[2];
    } else {
        return 'error';
    }
}
function getVideoEmbed(url){
	var id = getVideoId(url);
	return '<iframe width="560" height="315" src="//www.youtube.com/embed/' + id + '" frameborder="0" allowfullscreen></iframe>';
}
function makeHyperLinkedFields(a){
	return a.map(function(e){
		var ida='';
		var idb='';
		var a='';
		for(n in e){
			if(n.indexOf('ID') != -1){
				console.log(n+" "+e[n]);
				ida='<a href="/d/'+n.substring(0,1)+'/'+e[n]+'">';	
				idb='</a>';
			}
			else if(n == 'src'){
				a=getVideoEmbed(e[n]);
			}
			else if(e[n].startsWith('http')){
				ida='<a href="'+e[n]+'">';
				idb='</a>';
				a=a+" "+e[n];
			}else{
				a=a+" "+e[n];
			}
		}
		return ida+a+idb;
	}).join('<span>,</span>');
	
}
function decodeEntities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}
function displaySearch(json,rq){
	searchResults=createTable('searchResults',content);
	if(json.length == 0){
		$('<tr><th>No results</th></tr>').appendTo(searchResults);
	}
	else {
		initializeTable(searchResults);
		json = displayCodes(json,searchResults,codes);
		displayTable(json,searchResults);
	}
}
function initializeTable(tableElement){
	caption=$('<caption>').prependTo(tableElement);
	thead=$('<thead>').appendTo(tableElement);
	tbody=$('<tbody>').appendTo(tableElement);
}
function addToCaption(e,id){
	var aa=$('<div>').append(e).appendTo(caption);
	if(id !== ''){
		aa.attr('id',id);
	}
	return aa;
}
function displayTable(json,tableElement){
	tableElement.addClass('vertTable');
	displayHeaderRow(json[0],thead);
	json.forEach(function(element){/*console.log(element);*/displayContentRow(element,tbody);});
	
}
function displayHeaderRow(e,t){ displayRow(e,t,'th');}
function displayContentRow(e,t){ displayRow(e,t,'td');}
function displayRow(e,t,columnTag){
	//console.log(e);
	var ct = columnTag;
	var tr=$('<tr>')
	var tp;
	var id;
	for(c in e){
		if(typeof e[c] === "object" && e[c] !== null){
			//last minute dirty fix
			tmp=e[c];
			delete e[c];
			dd=Object.assign(tmp,e);
			//console.log(dd);
			displayRow(dd,t,columnTag);
			return '';
		}else{
			if(c.indexOf('ID') != -1){
				id=e[c];
				tp=c.substring(0,1);
			}
			else{
				var v;
				//console.log(c+" "+e);
				switch(ct){
					case 'th': v=c; break;
					default : 
						v=nulltoUnknown(e[c]);
						if(c=='name'|| c=='title'){
							v='<a href="/d/'+tp+'/'+id+'">'+v+'</a>';
						}
				};
				$('<'+ct+'>'+v+'</'+ct+'>').appendTo(tr);
			}
		}
	}
	tr.appendTo(t);
}
function nulltoUnknown(s){
	if(s == null || s == 'null'){
		s='NC';
	}
	return s
}
  </script>
  </head>
  <body>
  </body>
</html>

