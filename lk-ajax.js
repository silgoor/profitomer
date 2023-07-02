//----------------------------------------------------------------------------------------------

let rowsPerPage=50;

let editElementId=null;
let editElementOldValue='';

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
/*function updateField(functionName, id, val, elementId)
{
}*/



function editStockIncost(id)
{
	editElementNumber('updateStocksIncost', id, 'incost'+id);
}

function editSupplyIncost(id)
{
	editElementNumber('updateSupplyIncost', id, 'incost'+id);
}
function editExpenseAmount(id)
{
	editElementNumber('updateExpenseAmount', id, 'expenseAmount'+id);
}




function editElementNumber(functionName, id, containerId)
{
	if(editElementId===containerId)
	{
		return;
	}
	let container=el(containerId);
	let val=parseInt(container.innerText);

	if(editElementId !== null)
	{
		let ee=el(editElementId);
		if(ee === null)
		{
			editElementId = null;
		}
		else
		{
			ee.innerText=editElementOldValue;
		}
//		editElementId=null;
	}
	editElementId=containerId;
	editElementOldValue=val;

	let mi = document.createElement("input");
	mi.setAttribute('id', id+'Input');
	mi.setAttribute('type', 'number');
	mi.value = val;
	let mb = document.createElement("button");
	mb.setAttribute('id', id+'Button');
	let request = {};
	request['_function']=functionName;
	request['id']=id;
	

	let onResponse=function(){container.innerText=mi.value;editElementId=null;};
	mb.onclick = function(){request['value']=mi.value; ajax(addIdAndAuth(request), onResponse)};
	mb.defaultValue = val;
	mb.innerText = 'OK';
	// Remove childs
	while (container.lastChild)
	{
		container.removeChild(container.lastChild);
	}
	// Add new childs.
	container.appendChild(mi);
	container.appendChild(mb);
/*	mi.setAttribute('class', 'w3-input w3-border');*/
}
//----------------------------------------------------------------------------------------------
function onNewExpenseClicked()
{
	if(el('newExpense').style.display=='none')
	{
		el('newExpense').style.display='block';
	}
	else
	{
		if(!el('expenseAmount').value)
		{
			alert('Введите сумму, пожалуйста');
			return;
		}
		if(!el('expenseDate').value)
		{
			alert('Введите дату, пожалуйста');
			return;
		}
		let request = {};
		request['_function']='insertExpense';
		request['amount']=el('expenseAmount').value;
		request['date']=el('expenseDate').value;
		request['notes']=el('expenseNotes').value;
		ajax(addIdAndAuth(request),onInsertExpenseResponse);
		el('newExpense').style.display='none';
	}
}
//----------------------------------------------------------------------------------------------
function onInsertExpenseResponse(response)
{
	getContent('getExpenses');
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
			el('content').innerHTML=getLoginForm();
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
function getLoginForm()
{
	return '<p><input id="lk_login" type="text" placeholder="Email" class="w3-input w3-border"  autocomplete="username"><br><input id="lk_password" type="password" placeholder="Пароль" class="w3-input w3-border" autocomplete="current-password"><br><button class="w3-button w3-black" id="lk_login_button" onclick="onLogin()">Войти</button><hr><button class="w3-button w3-black" onclick="loadPageWithHistoryPush(\'?t=registration\');">Зарегистрироваться</button></p>';
}
//----------------------------------------------------------------------------------------------
function loadRegistrationPage()
{
	el('content').innerHTML='<div class="w3-container"><input id="lk_login" type="text" placeholder="Email" class="w3-input w3-border"><input id="lk_phone" type="text" placeholder="Телефон +7..." class="w3-input w3-border"><input id="lk_telegram" type="text" placeholder="Ник телеграм @..." class="w3-input w3-border"><input id="lk_name" type="text" placeholder="Имя" class="w3-input w3-border"><br><button class="w3-button w3-black" onclick="onRegistration()">Зарегистрироваться</button></div>';
}
//----------------------------------------------------------------------------------------------
function onRegistration()
{
	console.log("registration");
	const urlParams = new URLSearchParams(window.location.search);
	let tariff = urlParams.get('tariff');

	let request = {};
	request["_function"]="createAccount";
	request["email"]=el('lk_login').value;
	request["telegram"]=el('lk_telegram').value;
	request["phone"]=el('lk_phone').value;
	request["surname"]='';
	request["name"]=el('lk_name').value;
	request["secondname"]='';
	if(tariff>0)
	{
		request["tariff"]=tariff;
	}
	ajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------
function onUpdateToken()
{
	let token=el('wbTokenInput').value.trim();
	if(token.length!==48 && token.length!==149)
	{
		alert("Неверная длина токена: "+token.length+" символов из 149.");
		return;
	}
	ajax(addIdAndAuth({'_function':'updateWb1Token','value':token}),onTokenUpdateResponse);
	let wbTokenBlock=el('wbTokenBlock');
	if(wbTokenBlock)
	{
		wbTokenBlock.style='display:none;';
		el('changeWbToken1Button').style='display:block;';
	}
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
		el('content').innerHTML=getLoginForm();
	}
}
//----------------------------------------------------------------------------------------------
function loadPage(queryString)
{
	console.log("loadPage: "+queryString);
	el('content').innerHTML='<img src="wait.png" class="rotate">';
	const urlParams = new URLSearchParams(queryString);
	let section = urlParams.get('t');
//	let formTableName = urlParams.get('f');
//	let cardTableName = urlParams.get('c');
	let page = urlParams.get('p');
	let debugSection = urlParams.get('d');
	if(debugSection!==null)
	{
		console.log('debug section');
		if(debugSection==='supplies')
		{
			getJsonContent('getWb1Supplies');
		}
		else if(debugSection==='supplies2')
		{
			getJsonContent('getWb2Supplies');
		}
		else if(debugSection==='stock')
		{
			getJsonContent('getWb1Stocks');
		}
		else if(debugSection==='sales')
		{
			getJsonContent('getWb1Sales');
		}
		return;
	}
	if(section!==null)
	{
		if(section==='dashboard')
		{
//			getContent('getDashboard');
			getContent('getDashboard',{'weekOffset':urlParams.get('weekoffset')});
		}
		else if(section==='stock')
		{
			getContent('getStocks');
		}
		else if(section==='stock2')
		{
			getContent('getStocks2');
		}
		else if(section==='product')
		{
			getContent('getProduct',{'barcode':urlParams.get('barcode')});
		}
		else if(section==='supplies')
		{
			getContent('getSupplies',{'incomeId':urlParams.get('id')});
		}
		else if(section==='orders')
		{
			getContent('getOrders');
		}
		else if(section==='weeklyplan')
		{
			getContent('getWeeklyPlan');
		}
		else if(section==='dailyplan')
		{
			getContent('getDailyPlan');
		}
		else if(section==='sales')
		{
			getContent('getSales');
		}
		else if(section==='expenses')
		{
			getContent('getExpenses');
		}
		else if(section==='load')
		{
			getContent('importData');
		}
		else if(section==='profile')
		{
			getContent('getProfile');
		}
		else if(section==='editWbToken')
		{
			getContent('editWbToken');
		}
		else if(section==='support')
		{
			getContent('getSupport');
		}
		else if(section==='about')
		{
			getContent('getAbout');
		}
		else if(section==='registration')
		{
			loadRegistrationPage();
		}
		else
		{
			alert('Не найден раздел '+section);
		}
		highlight('mainMenu','mainMenu-'+section);
//		menuHighlight(section);
		return;
	}
	else // Default.
	{
		getContent('getAbout');
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
function getContent(funcName,requestOpt)
{
	let request = {};
	request["_function"]=funcName;
	if(requestOpt)
	{
		request = Object.assign(request, requestOpt);
	}
	ajax(addIdAndAuth(request),onContentResponse);
}
//----------------------------------------------------------------------------------------------
function onContentResponse(response)
{
	el('content').innerHTML=response["responseData"];
}
//----------------------------------------------------------------------------------------------
function getJsonContent(funcName,requestOpt)
{
	let request = {};
	request["_function"]=funcName;
	if(requestOpt)
	{
		request = Object.assign(request, requestOpt);
	}
	ajax(addIdAndAuth(request),onJsonContentResponse);
}
//----------------------------------------------------------------------------------------------
function onJsonContentResponse(response)
{
	let obj = JSON.parse(response["responseData"]); // Reencode to human-readable json.
	el('content').innerHTML='<pre>'+JSON.stringify(obj, null, 2)+'</pre>';
}
//----------------------------------------------------------------------------------------------
function onYandexCreatePayment(accountId, amount)
{
// Создание заказа
	let request = {};
	request["_id"]=new Date(); // Миллисекунд с начала эпохи.
	request["_function"]="yookassaCreatePayment";
	request["accountId"]=accountId;
	request["amount"]=amount;
	ajaxPostRequestPath('ajax_yookassa.php',request,onYandexCreatePaymentResponse);
}
//--------------------------------------------------------------------------------------
function onYandexCreatePaymentResponse(response)
{
	if(response.hasOwnProperty('redirectUrl'))
		window.location.replace(response['redirectUrl']);
	else
		alert("При переходе на страницу оплаты произошла ошибка.");
}
//--------------------------------------------------------------------------------------
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
function onWeekStatisticsCSVResponse(response)
{
	save('statistics.csv',response["responseData"]);
}

//----------------------------------------------------------------------------------------------
function importSelfPurchasesFromCSV(input)
{
	let file = input.files[0];
	let reader = new FileReader();
	reader.readAsText(file);
	
	reader.onload = function()
	{
		let request = {'_function':'importSelfPurchasesFromCSV'};
		request['csv']=reader.result;
		ajax(addIdAndAuth(request),onContentResponse);
		console.log(reader.result);
	};
}
//----------------------------------------------------------------------------------------------
/*function onImportSelfPurchasesFromCSVResponse(response)
{
}*/
//----------------------------------------------------------------------------------------------






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
