/**
* Support routines for PHPXref
* (c) Gareth Watts 2003-2004
*/

// Number of items that are held in the user's search history cookie
// The cookie spec says up to 4k per cookie, so at ~50 bytes per entry
// that gives a maximum of around 80 items as a max value for this field
var MAX_SEARCH_ITEMS=25; 



/**
** Simple dynamic HTML popup handler
** (c) Gareth Watts, August 2003
*/
var gwActivePopup=null; // global
var gwTimeoutId=0;
function gwPopup(e,layerid, noautoclose) {
    var isEvent=true;
    var x=null; var y=null;

    gwCloseActive();
    try { e.type } catch (e) { isEvent=false; }
    if (isEvent) {
        if (e.pageX||e.pageY) {
            x=e.pageX; y=e.pageY;
        } else if (e.clientX||e.clientY) {
            if (document.documentElement && document.documentElement.scrollTop) {
                x=e.clientX+document.documentElement.scrollLeft; y=e.clientY+document.documentElement.scrollTop;
            } else {
                x=e.clientX+document.body.scrollLeft; y=e.clientY+document.body.scrollTop;
            }
        } else {
            return; 
        }
    } else if (e != null) { /* assume it's an element */
        x=elementX(e);
        y=elementY(e);
    }
    layer=document.getElementById(layerid);
    if (x != null) {
        layer.style.left=x;
        layer.style.top=y;
    }
    layer.style.visibility='Visible';
    gwActivePopup=layer;
    clearTimeout(gwTimeoutId); gwTimeoutId=0;
    if (!noautoclose) {
        gwTimeoutId=setTimeout("gwCloseActive()", 5000);
        layer.onmouseout=function() { clearTimeout(gwTimeoutId); gwTimeoutId=setTimeout("gwCloseActive()", 350); } 
        layer.onmouseover=function() { clearTimeout(gwTimeoutId); gwTimeoutId=0;}
    }
}

/**
* Close the active popup
*/
function gwCloseActive() {
    if (gwActivePopup) {
        gwActivePopup.style.visibility='Hidden';
        gwActivePopup=null;
    }
}

spTimeoutId=0;
function showSearchBox() {
    popup=document.getElementById('searchbox');
    popup.style.visibility='Visible';
    clearTimeout(spTimeoutId); spTimeoutId=0;
    spTimeoutId=setTimeout('closeSearchBox()', 5000);
    popup.onmouseout=function() { clearTimeout(spTimeoutId); spTimeoutId=setTimeout('closeSearchBox()', 350); }
    popup.onmouseover=function() { clearTimeout(spTimeoutId); spTimeoutId=0;}
}

function closeSearchBox() {
    popup=document.getElementById('searchbox');
    popup.style.visibility='Hidden';
}

/**
* Display the hover-over-function-name popup
*/
function funcPopup(e, encfuncname) {
    gwCloseActive();
    var title=document.getElementById('func-title');
    var body=document.getElementById('func-body');
    var desc=document.getElementById('func-desc');

    var funcdata=FUNC_DATA[encfuncname];
    title.innerHTML=funcdata[0]+'()';
    desc.innerHTML=funcdata[1];
    var bodyhtml='';
    var deflist=funcdata[2];
    var defcount=deflist.length;
    var funcurl=relbase+'_functions/'+encfuncname+'.html';
    if (defcount>0) {
        var pl=defcount==1 ? '' : 's';
        bodyhtml+=defcount+' definition'+pl+':<br>\n';
        for(var i=0;i<defcount;i++) {
             var dir=deflist[i][0];
             if (dir!='') { dir+='/'; }
             if (deflist[i][0]) {
                 var target = deflist[i][0]+'/'+deflist[i][1]+'.source'+ext+'#l'+deflist[i][2];
             } else {
                 var target = deflist[i][1]+'.source'+ext+'#l'+deflist[i][2];
             }
             bodyhtml+='&nbsp;&nbsp;<a onClick="logFunction(\''+encfuncname+'\', \''+target+'\')" href="'+relbase+target+'">'+dir+deflist[i][1]+'</a><br>\n';
            /* bodyhtml+='&nbsp;&nbsp;<a onClick="logFunction(\''+encfuncname+'\')" href="'+relbase+deflist[i][0]+'/'+deflist[i][1]+'.source'+ext+'#l'+deflist[i][2]+'">'+dir+deflist[i][1]+'</a><br>\n'; */
        }       
    } else {
        bodyhtml+='No definitions<br>\n';
    }
    bodyhtml+='<br>Referenced <a href="'+funcurl+'">'+funcdata[3]+' times</a><br>\n';
    body.innerHTML=bodyhtml;

    gwPopup(e, 'func-popup');
}

/**
* Display the popup for built-in PHP functions
*/
function phpfuncPopup(e, encfuncname) {
    gwCloseActive();
    var title=document.getElementById('func-title');
    var body=document.getElementById('func-body');
    var desc=document.getElementById('func-desc');

    var funcdata=FUNC_DATA[encfuncname];
    var funcname=funcdata[0];
    var funcurl=relbase+'_functions/'+encfuncname+'.html';

    title.innerHTML='PHP: '+funcname+'()';
    desc.innerHTML='Native PHP function';
    var funcnameenc=escape(funcname);
    var bodyhtml='Documentation: <a href="http://php.net/'+encfuncname+'" target="_new">'+funcname+'()</a><br>';
    bodyhtml+='<br>Referenced <a href="'+funcurl+'">'+funcdata[3]+' times</a><br>\n';

    body.innerHTML=bodyhtml;
    gwPopup(e, 'func-popup');
}


/**
* Display the hover-over-class popup
*/
function classPopup(e, encclassname) {
    gwCloseActive();
    var title=document.getElementById('class-title');
    var body=document.getElementById('class-body');
    var desc=document.getElementById('class-desc');

    var classdata=CLASS_DATA[encclassname];
    title.innerHTML=classdata[0]+'::';
    desc.innerHTML=classdata[1];
    var bodyhtml='';
    var deflist=classdata[2];
    var defcount=deflist.length;
    var classurl=relbase+'_classes/'+encclassname+'.html';
    if (defcount>0) {
        var pl=defcount==1 ? '' : 's';
        bodyhtml+=defcount+' definition'+pl+':<br>\n';
        for(var i=0;i<defcount;i++) {
             var dir=deflist[i][0];
             if (dir!='') { dir+='/'; }
             if (deflist[i][0]) {
                 var target = deflist[i][0]+'/'+deflist[i][1]+'.source'+ext+'#l'+deflist[i][2];
             } else {
                 var target = deflist[i][1]+'.source'+ext+'#l'+deflist[i][2];
             }
             bodyhtml+='&nbsp;&nbsp;<a onClick="logClass(\''+encclassname+'\', \''+target+'\')" href="'+relbase+target+'">'+dir+deflist[i][1]+'</a><br>\n';
        }       
    } else {
        bodyhtml+='No definitions<br>\n';
    }
    bodyhtml+='<br>Referenced <a href="'+classurl+'">'+classdata[3]+' times</a><br>\n';
    body.innerHTML=bodyhtml;

    gwPopup(e, 'class-popup');
}


/**
* Display the hover-over-constant popup
*/
function constPopup(e, constname) {
    gwCloseActive();
    var title=document.getElementById('const-title');
    var body=document.getElementById('const-body');
    var desc=document.getElementById('const-desc');

    var constdata=CONST_DATA[constname];
    title.innerHTML='Const: '+constdata[0];
    desc.innerHTML=constdata[1];
    var bodyhtml='';
    var deflist=constdata[2];
    var defcount=deflist.length;
    var consturl=relbase+'_constants/'+constname+'.html';
    if (defcount>0) {
        var pl=defcount==1 ? '' : 's';
        bodyhtml+=defcount+' definition'+pl+':<br>\n';
        for(var i=0;i<defcount;i++) {
             var dir=deflist[i][0];
             if (dir!='') { dir+='/'; }
             if (deflist[i][0]) {
                 var target = deflist[i][0]+'/'+deflist[i][1]+'.source'+ext+'#l'+deflist[i][2];
             } else {
                 var target = deflist[i][1]+'.source'+ext+'#l'+deflist[i][2];
             }
             bodyhtml+='&nbsp;&nbsp;<a onClick="logConstant(\''+constname+'\', \''+target+'\')" href="'+relbase+target+'">'+dir+deflist[i][1]+'</a><br>\n';
        }       
    } else {
        bodyhtml+='No definitions<br>\n';
    }
    bodyhtml+='<br>Referenced <a href="'+consturl+'">'+constdata[3]+' times</a><br>\n';
    body.innerHTML=bodyhtml;

    gwPopup(e, 'const-popup');
}

/**
* Display the hover-over-function-require (or include) popup
*/
function reqPopup(e, name, baseurl) {
    gwCloseActive();
    var title=document.getElementById('req-title');
    var body=document.getElementById('req-body');

    title.innerHTML=name;
    body.innerHTML='<a href="'+baseurl+'.source'+ext+'">Source</a>&nbsp;|&nbsp;'
        +'<a href="'+baseurl+ext+'">Summary</a>';
    gwPopup(e, 'req-popup');
}


/**
* Handle setting up the navigation frame, if the user
* has the option turned on in a cookie
*/
function handleNavFrame(relbase, subdir, filename) {
    var line = '';
    var navstatus=gwGetCookie('xrefnav');
    if (navstatus!='off' && (parent.name!='phpxref' || parent==self)) {
        if (subdir!='') { subdir+='/'; }
        var x=parent.location.toString().indexOf('#');
        if (x != -1) { /* Preserve the line number referenced in the parent */
            line = parent.location.toString().substr(x);
        }
        parent.location=relbase+'nav'+ext+'?'+subdir+filename+line;
    } else if (parent.nav && parent.nav.open_branch) {
        parent.nav.open_branch(subdir);
    }
}

/**
* Fetch a cookie by name
*/
function gwGetCookie(name) {
    var cookies=document.cookie;
    var offset; var endpoint;
    name = cookiekey + '-' + name;
    if ((offset=cookies.indexOf(name))==-1)
        return null;
    if ((endpoint=cookies.indexOf(';', offset))==-1)
        endpoint=cookies.length;
    var value=unescape(cookies.substring(offset+name.length+1, endpoint));
    return value;
}

/**
* Set an individual cookie by name and value
*/
function gwSetCookie(name, value) {
    var expire = new Date();
    expire.setTime( expire.getTime() + 86400*365*1000 );
    name = cookiekey + '-' + name;
    document.cookie=name+'='+escape(value)+';path=/;expires='+expire.toGMTString();
}

/**
* Switch on the navigation frame 
*/
function navOn() {
    gwSetCookie('xrefnav','on');
    self.location.reload();
}

/** 
* Turn off the navigation frame
*/
function navOff() {
    gwSetCookie('xrefnav','off');
    parent.location.href=self.location.href;
}

/**
* Escape search strings
*/
function pesc(str) {
    str=str.replace(/^(con|prn|aux|clock\\$|nul|lpt\\d|com\\d)\$/i, "-\$1");
    str=str.replace(/^(con|prn|aux|clock\\$|nul|lpt\\d|com\\d)\\./i, "-\$1.");
    return str;
}

/**
* Run a 'search'
*/
function jump() {
    if (document.search.classname.value.length) {
        jumpClass(document.search.classname.value);
    }
    if (document.search.funcname.value.length) {
        jumpFunction(document.search.funcname.value);
    }
    if (document.search.varname.value.length) {
        jumpVariable(document.search.varname.value);
    }
    if (document.search.constname.value.length) {
        jumpConstant(document.search.constname.value);
    }
    if (document.search.tablename.value.length) {
        jumpTable(document.search.tablename.value);
    }
    return false;
}

/**
* Jump to a function reference page, and log in the history log
*/
function jumpFunction(target) {
    var target=target.replace(/[()]/g,'');
    target=target.toLowerCase();
    logFunction(target);
    window.location=relbase+'_functions/'+escape(escape(pesc(target)))+ext;
}

/**
* Jump to a class reference page, and log in the history log
*/
function jumpClass(target) {
    var target=target.replace(/[()]/g,'');
    target=target.toLowerCase();
    logClass(target);
    window.location=relbase+'_classes/'+escape(escape(pesc(target)))+ext;
}

/**
* Jump to a variable reference page, and log in the history log
*/
function jumpVariable(target) {
    var target=target.replace(/[\$]/g,'');
    logVariable(target);
    window.location=relbase+'_variables/'+escape(escape(pesc(target)))+ext;
}

/**
* Jump to a constant reference page, and log in the history log
*/
function jumpConstant(target) {
    var target=target.toUpperCase();
    logConstant(target);
    window.location=relbase+'_constants/'+escape(escape(pesc(target)))+ext;
}

/**
* Jump to a table reference page, and log in the history log
*/
function jumpTable(target) {
    var target=toLowerCase();
    logTable(target);
    window.location=relbase+'_tables/'+escape(escape(pesc(target)))+ext;
}

/**
* Log a function access in the history log
* If urltarget is supplied, then the specific URL where
* that function is defined is logged with the function name
*/
function logFunction(target, urltarget) {
    var uritarget = '';
    if (urltarget)
        uritarget = ';' + escape(urltarget)
    addToSearchList('F' + escape(target) + uritarget);
    return true;
}

/**
* Log a class access in the history log
*/
function logClass(target, urltarget) {
    var uritarget = '';
    if (urltarget)
        uritarget = ';' + escape(urltarget)
    addToSearchList('C' + escape(target) + uritarget);
    return true;
}

/**
* Log a variable access in the history log
*/
function logVariable(target, urltarget) {
    var uritarget = '';
    if (urltarget)
        uritarget = ';' + escape(urltarget)
    addToSearchList('V' + escape(target) + uritarget);
    return true;
}

/**
* Log a constant access in the history log
*/
function logConstant(target, urltarget) {
    var uritarget = '';
    if (urltarget)
        uritarget = ';' + escape(urltarget)
    addToSearchList('A' + escape(target) + uritarget);
    return true;
}

/**
* Log a table access in the history log
*/
function logTable(target) {
    addToSearchList('T' + escape(target));
    return true;
}

/**
* Get an array of previous searches/lookups
*/
function getSearchList() {
    var searchlist=gwGetCookie('xrefsearch');
    if (!searchlist) {
        return false;
    }
    return searchlist.split(',');
}

/**
* Add a new entry to the search history log
*/
function addToSearchList(item) {
    if (typeof Array.prototype.unshift == 'undefined') {
        return false;
    }
    item.replace(/[^\w_]+/gi, '');
    if (!item.length)
        return false; // no point adding an empty string

    var searchlist=getSearchList();
    if (!searchlist) {
        searchlist = Array();
    }
    // Remove duplicate entries
    for (var i=searchlist.length-1; i>=0; i--) {
        if (searchlist[i] == item) {
            searchlist.splice(i,1);
        }
    }
    searchlist.unshift(item); // push newest onto the beginning of the list
    if (searchlist.length > MAX_SEARCH_ITEMS) {
        searchlist.splice(MAX_SEARCH_ITEMS);
    }
    gwSetCookie('xrefsearch', searchlist.join(','));
    return true;
}

/**
* Calculate the absolute X offset of an html element
*/
function elementX(el) {
    var x = el.offsetLeft;
    var parent = el.offsetParent;
    while (parent) {
        x += parent.offsetLeft;
        parent = parent.offsetParent;
    }
    return x;
}

/**
* Calculate the absolute Y offset of an html element
*/
function elementY(el) {
    var y = el.offsetTop;
    var parent = el.offsetParent;
    while (parent != null) {
        y += parent.offsetTop;
        parent = parent.offsetParent;
    }
    return y;
}

/**
* Display a popup containing the user's most recent searches
*/
function showSearchPopup() {
    gwCloseActive();
    var title=document.getElementById('searchpopup-title');
    var body=document.getElementById('searchpopup-body');

    var searchlist=getSearchList();
    title.innerHTML='Access History';
    if (!searchlist) {
        body.innerHTML='<p align="center">(no entries logged)</p>';
    } else {
        var content = '<table>';
        for (var i=0; i<searchlist.length; i++) {
            var parse = searchlist[i].split(';');
            var item = unescape(parse[0]);
            var itemsource = parse.length>1 ? unescape(parse[1]) : '';
            var type = item.charAt(0);
            var ref = item.substr(1);
            switch (type) {
                case 'F':
                    var href = "javascript:jumpFunction('" + ref + "')"
                    var name = ref + "()";
                    var styleclass = 'function';
                    break;
                case 'C':
                    var href = "javascript:jumpClass('" + ref + "')"
                    var name = ref + "::";
                    var styleclass = "class";
                    break;
                case 'V':
                    var href = "javascript:jumpVariable('" + ref + "')"
                    var name = '$' + ref;
                    var styleclass = "var";
                    break;
                case 'A':
                    var href = "javascript:jumpConstant('" + ref + "')"
                    var name = ref;
                    var styleclass = "const";
                    break;
                case 'T':
                    var href = "javascript:jumpTable('" + ref + "')"
                    var name = 'SQL: ' + ref;
                    var styleclass = '';
                    break;
                default:
                    continue; // unknown type
            }
            content += '<tr>';
            if (itemsource) {
                content += '<td>[<a href="' + relbase + itemsource + '">S</a>]</td>';
            } else {
                content += '<td align="right">&raquo;</td>';
            }
            content += '<td><a href="' + href + '"';
            if (styleclass)
                content += ' class="' + styleclass + '"';
            content += '>' + name + '</a></td></tr>';
        }
        content += '</table>';
        body.innerHTML = content;
    }
    position=document.getElementById('funcsearchlink');
    gwPopup(null, 'search-popup', true);
}               

function hilite(itemid) {
    /* only mozilla and IE are really supported here atm. */
    try { 
    var agt=navigator.userAgent.toLowerCase();
    if (agt.indexOf('gecko') != -1) {
        document.getElementById('hilight').sheet.insertRule('.it'+itemid+' { background-color: #0f0; }', 0);
    } else {
        document.styleSheets["hilight"].addRule('.it'+itemid, 'background-color: #0f0', 0);
    }
    } catch(e) { } /* swallow any browser incompatibility errors */
}

function lolite() {
    try { 
    var agt=navigator.userAgent.toLowerCase();
    if (agt.indexOf('gecko') != -1) {
        document.getElementById('hilight').sheet.deleteRule(0);
    } else {
        document.styleSheets["hilight"].removeRule(0);
    }
    } catch(e) { }
}
