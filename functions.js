function mURLin_ValidateURLForm()
{
    var host;
    var URL;
    var timeout;
    var proxyserver;
    var proxyenabled;
    
    var result = true;
    
    // Validate each entry
    host = document.getElementById('selected_host');
    if (host.value === "")
    {
       alert("You must select a host to map the URL to.")
       result = false;
    }
    
    URL = document.getElementById('url');
    if (!ValidateURL(URL.value))
    {
       URL.style.background = '#FFAAAA';
       result = false;
    }
    else
    {
       URL.style.background = '#FFFFFF';
    }
    
    if ((document.getElementById('text_match').value).length >= 2048)
    {
       (document.getElementById('text_match')).style.background = '#FFAAAA';
       result = false;
    }
    else
    {
       (document.getElementById('text_match')).style.background = '#FFFFFF';
    }
    
    timeout = document.getElementById('timeout');
    if (timeout.value < 0 || timeout.value > 100 || timeout.value === "")
    {
       timeout.style.background = '#FFAAAA';
       result = false;
    }
    else
    {
       timeout.style.background = '#FFFFFF';
    }
    
    proxyserver = document.getElementById('proxyaddress');
    proxyenabled = document.getElementById('proxyserver');
    if (proxyserver.value == "" && proxyenabled.checked == true)
    {
       proxyserver.style.background = '#FFAAAA';
       result = false;
    }
    else
    {
       proxyserver.style.background = '#FFFFFF';
    }

    return result;
}

function ValidateURL(url)
{
    if (url.length < 2048)
      return /^(ftp|http|https):\/\/[^ "]+$/.test(url);
    else 
        return false;
}


function mURLin_test_match(text_match, url, timeout, proxyserver, proxyaddress, proxyusername, proxypassword)
{
    var proxy = "";
    
    if (proxyserver === true)
        proxy = "&proxy=" + proxyaddress;
    
    $.get("./scripts/test_regex.php?regex=" + encodeURIComponent(text_match) + "&url=" + encodeURIComponent(url) + "&timeout=" + timeout + proxy + "&proxyusername=" + proxyusername + "&proxypassword=" + proxypassword, function(result) 
    {
        alert(result);
    });    
}

function mURLin_open_url(url, timeout, proxyserver, proxyaddress, proxyusername, proxypassword)
{
    var proxy = "";
    
    if (proxyserver === true)
        proxy = "&proxy=" + proxyaddress;
    
    window.open('showpage.php?page=' + encodeURIComponent(url) + "&timeout=" + timeout + proxy + "&proxyusername=" + proxyusername + "&proxypassword=" + proxypassword, 'urlWin', 'toolbar=no, directories=no, location=no, status=yes, menubar=no, resizable=yes, scrollbars=yes, width=600, height=400');
}

function CheckAll(id, value)
{
    var checkboxes = document.getElementsByName(id);

    for(index = 0; index < checkboxes.length; ++index)
        {
        checkboxes[index].checked = value;
        }
}

function CheckAllFunction(state, allchk)
{
    if(!state.checked)
    {
        var allbox = document.getElementById(allchk);
        allbox.checked = false;
    }
}

function CheckBox(chkbox, allchk)
{
    // Check the box and uncheck the select all box if required
    var box = document.getElementById(chkbox);
        
    box.checked = !box.checked;
    
    CheckAllFunction(box, allchk);
    
}

function mURLin_SelectHostClick(show)
{
    var shade = document.getElementById('shade');
    var popup = document.getElementById('popup');
    
    var display;
    if(show)
        display = 'inline';
    else
        display = 'none';
    
    shade.style.display = display;
    popup.style.display = display;
    
}

function mURLin_RefreshHosts(selectedhost, filter)
{
    $.get("./scripts/get_hosts.php?id=" + selectedhost + "&filter=" + filter, function(result) 
    {
        document.getElementById('hosttable').innerHTML = result;
    });  
}

function mURLin_SelectNewHost(hostid)
{
    // Select new host 'hostid'
    $.get("./scripts/get_hosts.php?action=info&id=" + hostid, function(result) 
    {
        // Split the result into 3...
        var s = result.split(",");
        document.getElementById('hostname_text').innerHTML = s[2];
        document.getElementById('dns_text').innerHTML = s[1];
        document.getElementById('selected_host').value = s[0];
    });  
    
    // Close the shade
    mURLin_SelectHostClick(false);
}

function mURLin_selectHost(hostid)
{
    document.getElementById(hostid).checked = true;
}

//function mURLin_CheckURLForm()
//{
//    // Check each element in the form for validity
//    var name = document.getElementById('id');
//    var url = document.getElementById('url');
//    var text_match = document.getElementById('text_match');
//    var timeout = document.getElementById('timeout');
//    
//    var result = true;
//    
//    if (name.value = "" || name.value == "NEW")
//    {
//        result = false;
//        name.bgcolor="light red";
//    }
//    
//    return result;
//}

