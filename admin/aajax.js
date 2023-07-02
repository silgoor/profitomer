//----------------------------------------------------------------------------------------------

//let currentMenuItem;
let ordersPage=0;
let ordersRowsPerPage=50;

let editElement=null;
let editElementOldValue='';

let tariffsArray=[[1,'Базовый'],[22,'Терра']];

document.addEventListener("DOMContentLoaded", function(event)
{
	loadByQuery();
});

window.addEventListener('popstate', function (e) {
    if (e.state !== null) {
        //load content with ajax
		console.log("onPopState");
		loadByQuery();
    }
});

window.onerror = function(msg, url, lineNo, columnNo, error)
{
	alert(msg);
	return false;
}

function el(id)
{
	return document.getElementById(id);
}
//----------------------------------------------------------------------------------------------
function editElementSelect(functionName, id, container, optionsArray)
{
	if(editElement===container)
	{
		console.log('click on editing container');
		return;
	}
	let val=container.innerText;

	if(editElement !== null)
	{
		editElement.innerText=editElementOldValue;
//		editElementId=null;
	}
	editElement=container;
	editElementOldValue=val;

	let ms = document.createElement("select");
	ms.id = id+'Select';
	for (var i = 0; i < optionsArray.length; i++)
	{
		let option = document.createElement("option");
		option.value = optionsArray[i][0];
		option.text = optionsArray[i][1];
		ms.appendChild(option);
	}
//	ms.value = val;
	
	let mb = document.createElement("button");
	mb.setAttribute('id', id+'Button');
	let request = {};
	request['_function']=functionName;
	request['id']=id;
	

	let onResponse=function(){container.innerText=ms.options[ms.selectedIndex].text;editElement=null;};
	mb.onclick = function(){request['value']=ms.value; aajax(addIdAndAuth(request), onResponse)};
	mb.defaultValue = val;
	mb.innerText = 'OK';
	// Remove childs
	while (container.lastChild)
	{
		container.removeChild(container.lastChild);
	}
	// Add new childs.
	container.appendChild(ms);
	container.appendChild(mb);
/*	mi.setAttribute('class', 'w3-input w3-border');*/
}
//----------------------------------------------------------------------------------------------
function escapeHtml(unsafe)
{
	let safe=unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
	return safe;
}
//--------------------------------------------------------------------------------------
function enterToBR(str)
{
	let newStr=str.replace(/\n/g, '<br>').replace(/\r/g, "");
	return newStr;
}
//--------------------------------------------------------------------------------------
function ajaxPostRequestPath(path,jsonRequest,onResponse) // данные запроса и колбэк-функция, принимающая данные ответа.
{
    var xhr = new XMLHttpRequest();
	xhr.onload = function (e)
	{
//		el('debug').innerHTML=e.currentTarget.responseText;// !!!!!!!!!!!!!!!!!!!!
		let responseText=e.currentTarget.responseText;
		let response;
		if(responseText.length===0)
		{
			console.log("Пустой ответ. Запрос: "+JSON.stringify(jsonRequest));
			onAjaxError();
			return;
		}
		try{
			response=JSON.parse(responseText);
		}
		catch(err)
		{
			console.log("Ошибка разбора ответа: "+responseText);
			onAjaxError();
			return;
		}
		if(response["_errorNumber"]==2 || response["_errorNumber"]==4)
		{
			showLoginForm();
			return;
		}
		if(onResponse)
		{
			onResponse(response);
		}
	};
	xhr.onerror = function (e)
	{
		console.log('Ошибка связи: '+e.target.status);
		onAjaxError();
	};
	xhr.open('POST', path);
	xhr.setRequestHeader('Content-type', 'application/json; charset=utf-8');
	xhr.send(JSON.stringify(jsonRequest));
}
//----------------------------------------------------------------------------------------------
function ajax(jsonRequest,onResponse)
{
	ajaxPostRequestPath('ajax.php',jsonRequest,onResponse);
}
//----------------------------------------------------------------------------------------------
function aajax(jsonRequest,onResponse) // Админские запросы
{
	ajaxPostRequestPath('aajax.php',jsonRequest,onResponse);
}
//----------------------------------------------------------------------------------------------
function onAjaxError()
{
	el('content').innerHTML='<h4>Произошла ошибка</h4><button class="w3-button w3-black" onclick="loadByQuery();">Перезагрузить страницу</button>';
}
//----------------------------------------------------------------------------------------------
function highlight(className, elementId)
{
	let elements = document.getElementsByClassName(className);
	for(let i = 0; i < elements.length; i++)
	{
		elements[i].className = 'w3-button '+className;
	}
	let menuElement=el(elementId);
	if(menuElement!==null)
	{
		menuElement.className='w3-button w3-2021-amethyst-orchid '+className;
	}
}
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
function showLoginForm()
{
	el('content').innerHTML='<div class="w3-container"><input id="lk_login" type="text" placeholder="Email" class="w3-input w3-border"  autocomplete="username"><br><input id="lk_password" type="password" placeholder="Пароль" class="w3-input w3-border" autocomplete="current-password"><br><button class="w3-button w3-black" id="lk_login_button" onclick="onLogin()">Войти</button></div><hr><button class="w3-button w3-black" onclick="loadRegistrationPage()">Зарегистрироваться</button>';
}
//----------------------------------------------------------------------------------------------
function loadRegistrationPage()
{
	el('content').innerHTML='<div class="w3-container"><input id="lk_login" type="text" placeholder="Email" class="w3-input w3-border"><input id="lk_phone" type="text" placeholder="Телефон +7..." class="w3-input w3-border"><input id="lk_name" type="text" placeholder="Имя" class="w3-input w3-border"><br><button class="w3-button w3-black" onclick="onRegistration()">Зарегистрироваться</button></div>';
}
//----------------------------------------------------------------------------------------------
function onRegistration()
{
	console.log("registration");
	let request = {};
	request["_function"]="createAccount";
	request["email"]=el('lk_login').value;
	request["phone"]=el('lk_phone').value;
	request["surname"]='';
	request["name"]=el('lk_name').value;
	request["secondname"]='';
	ajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------
function onTokenUpdateResponse()
{
	if(confirm("Токен сохранён. Загрузить данные с wildberries?") != true)
	{
		return;
	}
	loadPageWithHistoryPush('?t=load');
}
//----------------------------------------------------------------------------------------------
function addIdAndAuth(request)
{
	request["_id"]=new Date(); // Миллисекунд с начала эпохи.
	request["_login"]=localStorage.getItem('login');
	request["_password"]=localStorage.getItem('password');
	return request;
}
//----------------------------------------------------------------------------------------------
function loadByQuery()
{
	console.log("loadByQuery");
	loadPage(window.location.search);
}
//--------------------------------------------------------------------------------------
function onLogin()
{
	localStorage.setItem('login',el("lk_login").value);
	localStorage.setItem('password',el("lk_password").value);
//	mainMenu();
	loadByQuery();
}
//--------------------------------------------------------------------------------------
function onLogout()
{
	if(confirm("Выйти из учётной записи?"))
	{
		localStorage.setItem('login','');
		localStorage.setItem('password','');
		showLoginForm();
	}
}
//----------------------------------------------------------------------------------------------
function loadPage(queryString)
{
	console.log("loadPage: "+queryString);
	el('content').innerHTML='<img src="../wait.png" class="rotate">';
	const urlParams = new URLSearchParams(queryString);
	let section = urlParams.get('t');
	let page = urlParams.get('p');
	if(section!==null)
	{
		if(section==='accounts')
		{
			aGetContent('accounts');
		}
		else if(section==='newaccounts')
		{
			aGetContent('newAccounts');
		}
		else if(section==='account')
		{
			aGetContent('account',{'id':urlParams.get('id')});
		}
		else if(section==='accountstocsv')
		{
			let request = {"_function":"accountsToCsv"};
			aajax(addIdAndAuth(request),onAccountsToCsvResponse);
		}
		highlight('mainMenu','mainMenu-'+section);
//		menuHighlight(section);
		return;
	}
	else // Default.
	{
		aGetContent('accounts');
	}
}
//----------------------------------------------------------------------------------------------
function onAccountsToCsvResponse(response)
{
	save('contacts.csv',response["responseData"]);
}
//----------------------------------------------------------------------------------------------
function save(filename, data)
{
    const blob = new Blob([data], {type: 'text/csv'});
    if(window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveBlob(blob, filename);
    }
    else{
        const elem = window.document.createElement('a');
        elem.href = window.URL.createObjectURL(blob);
        elem.download = filename;        
        document.body.appendChild(elem);
        elem.click();        
        document.body.removeChild(elem);
    }
}
//----------------------------------------------------------------------------------------------
function loadPageWithHistoryPush(queryString)
{
	loadPage(queryString);
	history.pushState(queryString, null, queryString);
}
//----------------------------------------------------------------------------------------------

// ACCOUNT
//----------------------------------------------------------------------------------------------
function createAccount(email, phone, uSurname, uName, uSecondname)
{
	let request = {};
	request["_function"]="createAccount";
	request["email"]=email;
	request["phone"]=phone;
	request["surname"]=uSurname;
	request["name"]=uName;
	request["secondname"]=uSecondname;
	ajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------
function activateAccount(id)
{
	if (confirm('Возможно клиент указал неправильный адрес электронной почты. Пароль от аккаунта можо высылать только на указанную в регистрации почту. Активировать?') != true)
	{
		return;
	}
	let request = {};
	request["_function"]="activateAccount";
	request["id"]=id;
	aajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------

// W B
//----------------------------------------------------------------------------------------------
function getSupplies(incomeId)
{
	let request = {};
	request["_function"]="getSupplies";
	request["incomeId"]=incomeId;
	ajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------
function onIncostEditStart(id)
{
	el('incost'+id).style.display='none';
	el('editIncost'+id).style.display='inline-block';
}
//----------------------------------------------------------------------------------------------
function onIncostEditEnd(id)
{
	el('incost'+id).style.display='inline';
	el('editIncost'+id).style.display='none';
	let incost=el('incostEditInput'+id).value;
	let request = {};
	request["_function"]="updateSupplyIncost";
	request["id"]=id;
	request["value"]=incost;
	ajax(addIdAndAuth(request),onUpdateIncostResponse);
}
//----------------------------------------------------------------------------------------------
function onUpdateIncostResponse(response)
{
	location.reload();
}
//----------------------------------------------------------------------------------------------
function aGetContent(funcName,requestOpt)
{
	let request = {};
	request["_function"]=funcName;
	if(requestOpt)
	{
		request = Object.assign(request, requestOpt);
	}
	aajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------
function onContentResponse(response)
{
	el('content').innerHTML=response["responseData"];
}
//----------------------------------------------------------------------------------------------
function showWb1Supplies(accountId)
{
	let request = {};
	request['_function']='wb1Supplies';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.add('w3-black');
	el("stocksBtn").classList.remove('w3-black');
	el("ordersBtn").classList.remove('w3-black');
	el("salesBtn").classList.remove('w3-black');
	el("supplies2Btn").classList.remove('w3-black');
	el("stocks2Btn").classList.remove('w3-black');
	el("orders2Btn").classList.remove('w3-black');
}
//----------------------------------------------------------------------------------------------
function showWb1Stocks(accountId)
{
	let request = {};
	request['_function']='wb1Stocks';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.remove('w3-black');
	el("stocksBtn").classList.add('w3-black');
	el("ordersBtn").classList.remove('w3-black');
	el("salesBtn").classList.remove('w3-black');
	el("supplies2Btn").classList.remove('w3-black');
	el("stocks2Btn").classList.remove('w3-black');
	el("orders2Btn").classList.remove('w3-black');
}
//----------------------------------------------------------------------------------------------
function showWb1Orders(accountId)
{
	let request = {};
	request['_function']='wb1Orders';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.remove('w3-black');
	el("stocksBtn").classList.remove('w3-black');
	el("ordersBtn").classList.add('w3-black');
	el("salesBtn").classList.remove('w3-black');
	el("supplies2Btn").classList.remove('w3-black');
	el("stocks2Btn").classList.remove('w3-black');
	el("orders2Btn").classList.remove('w3-black');
}
//----------------------------------------------------------------------------------------------
function showWb1Sales(accountId)
{
	let request = {};
	request['_function']='wb1Sales';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.remove('w3-black');
	el("stocksBtn").classList.remove('w3-black');
	el("ordersBtn").classList.remove('w3-black');
	el("salesBtn").classList.add('w3-black');
	el("supplies2Btn").classList.remove('w3-black');
	el("stocks2Btn").classList.remove('w3-black');
	el("orders2Btn").classList.remove('w3-black');
}
//----------------------------------------------------------------------------------------------
function showWb2Supplies(accountId)
{
	let request = {};
	request['_function']='wb2Supplies';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.remove('w3-black');
	el("stocksBtn").classList.remove('w3-black');
	el("ordersBtn").classList.remove('w3-black');
	el("salesBtn").classList.remove('w3-black');
	el("supplies2Btn").classList.add('w3-black');
	el("stocks2Btn").classList.remove('w3-black');
	el("orders2Btn").classList.remove('w3-black');
}
//----------------------------------------------------------------------------------------------
function showWb2Stocks(accountId)
{
	let request = {};
	request['_function']='wb2Stocks';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.remove('w3-black');
	el("stocksBtn").classList.remove('w3-black');
	el("ordersBtn").classList.remove('w3-black');
	el("salesBtn").classList.remove('w3-black');
	el("supplies2Btn").classList.remove('w3-black');
	el("stocks2Btn").classList.add('w3-black');
	el("orders2Btn").classList.remove('w3-black');
}
//----------------------------------------------------------------------------------------------
function showWb2Orders(accountId)
{
	let request = {};
	request['_function']='wb2Orders';
	request['accountId']=accountId;
	aajax(addIdAndAuth(request),onShowJsonResponse);
	el('accountData').innerHTML='<img src="../wait.png" class="rotate">';
	el("suppliesBtn").classList.remove('w3-black');
	el("stocksBtn").classList.remove('w3-black');
	el("ordersBtn").classList.remove('w3-black');
	el("salesBtn").classList.remove('w3-black');
	el("supplies2Btn").classList.remove('w3-black');
	el("stocks2Btn").classList.remove('w3-black');
	el("orders2Btn").classList.add('w3-black');
}
//----------------------------------------------------------------------------------------------
function onShowJsonResponse(response)
{
	let obj = JSON.parse(response["responseData"]); // Reencode to human-readable json.
	el('accountData').innerHTML='<pre>'+JSON.stringify(obj, null, 2)+'</pre>';
}
//----------------------------------------------------------------------------------------------

// O R D E R S
//----------------------------------------------------------------------------------------------
/*function mainMenu()
{
	let request = {};
	request["_function"]="mainMenu";
	ajax(addIdAndAuth(request),setTitleFromResponse);
}*/
//----------------------------------------------------------------------------------------------
function setTitleFromResponse(response)
{
	el('contentTitle').innerHTML=response["responseData"];
}
//----------------------------------------------------------------------------------------------
function setFormValues(values)
{
	console.log(values)
	for (let key of Object.keys(values))
	{
		let elem=el(key);
		if(elem!==null)
		{
			elem.value=values[key];
		}
		else
		{
			console.log("Element not found: "+key);
		}
	}
}
//----------------------------------------------------------------------------------------------
function onSaveResponse(response)
{
	if(response.hasOwnProperty("_errorNumber") && response["_errorNumber"]!=0)
	{
		alert("На сервере что-то пошло не так. Он передал: "+response["_errorString"]);
		return;
	}
//	loadPageWithHistoryPush("?t=agreement");
	window.history.back();
}
//----------------------------------------------------------------------------------------------
