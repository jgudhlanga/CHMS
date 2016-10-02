/**
 * Created by jimlink on 2/20/2015.
 */

/*==================================================================TABS==========================================================================*/

$(function() {
    var tabs = $( "#tabs" ).tabs();
    tabs.find( ".ui-tabs-nav" ).sortable({
        axis: "x",
        stop: function() {
            tabs.tabs( "refresh" );
        }
    });
});

/*=============================================================================DATE PICKER==========================================================*/
$(function()
{
    $( "#datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true
    });
});


$(document).ready(function() {
    $(".date").each(function() {
        $(this).datepicker({
            dateFormat: "mm/dd/yy",
            changeMonth: true,
            changeYear: true
        });
    });
});

/*==============================================================================ACCORDION===========================================================*/

$(function() {
    var icons = {
        header: "ui-icon-circle-arrow-e",
        activeHeader: "ui-icon-circle-arrow-s"
    };
    $( "#accordion" ).accordion({
        icons: icons,
        heightStyle: "content",
        active: 'none',
        collapsible: true
    });
});

/*========================================================== OTHERS ===========================================================================*/

function confirmDelete(url, id)
{
    if(confirm("This will delete the item from the database. Are you sure you want to continue?"))
    {
        location.href=(""+url+"command/Delete/id/"+id);
    }
    else
        return false;
}

function url(url)
{
    if (url != '') location.href=''+url+'';
}

//apply style to the image for expand and collapse
$(function(){
    /*assign height of middleBar div to rightBar div*/
    $('.rightBar').height($('.middleBar').height());

    /* Top Bar Right Ul list */
    $('#jjdev-drop-down-anchor').click(function(){
        $('.jjdev-drop-down-list').toggle();
    });
    /* Sb Admin Toggle script */
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });

})

function toggleContent(row)
{
    var myUrl = $('#my-url').val();
    var myTheme = $('#my-theme').val();
    if ($('img[rel=' + row + '_exp]').attr("src") ==  myUrl + "/img/"+myTheme+"/col.png")
    {
        $('img[rel='+row+'_exp]').attr({src: myUrl+  "/img/"+myTheme+"/exp.png", title: "Expand"});
    }
    else
    {
        $('img[rel=' + row + '_exp]').attr({src: myUrl + "/img/"+myTheme+"/col.png", title: "Collapse"});
        //$('table tr.content_'+row).css({display: ""});
    }
    $('.content_' + row).toggle();
}


function createField(parentDiv, fieldType, fieldName, fieldLabel, fieldSize, sClass)
{
    var newDiv = document.createElement('div');
    switch (fieldType)
    {
        case 'text':
            newDiv.innerHTML = fieldLabel + '&nbsp; <input type="text" class="' + sClass + '" name="' + fieldName + '" size="'+fieldSize+'">';
            break;
        case 'textarea':
            newDiv.innerHTML = fieldLabel + '&nbsp; <textarea  class="' + sClass + '" name="' + fieldName + '" cols="'+fieldSize+'"></textarea>';
            break;
        case 'file':
            newDiv.innerHTML = fieldLabel + '&nbsp;<input type="file" name="' + fieldName + '">';
            break;
        case 'radio':
            newDiv.innerHTML = fieldLabel + '&nbsp; <input type="radio" class="' + sClass + '" name="' + fieldName + '">';
            break;
        case 'checkbox':
            newDiv.innerHTML = fieldLabel + '&nbsp; <input type="checkbox" class="' + sClass + '" name="' + fieldName + '">';
            break;
    }
    document.getElementById(parentDiv).appendChild(newDiv);
}

function checkAll(formID, elementType)
{
    var parentElem = document.getElementById('parent');
    var elementsObj = document.getElementById(''+formID+'').elements;
    for(var i=0; i < elementsObj.length;  i++ )
    {
        if(elementsObj[i].type == elementType)
        {
            if(parentElem.checked == true )
                elementsObj[i].checked = true;
            else
                elementsObj[i].checked = false;
        }
    }
}



