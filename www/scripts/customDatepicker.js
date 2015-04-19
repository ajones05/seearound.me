var monthtext = ['Month','01','02','03','04','05','06','07','08','09','10','11','12'];
var daytext = ['Day','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'];
var days=[0,31,28,31,30,31,30,31,31,30,31,30,31];
var daysLeap=[0,31,29,31,30,31,30,31,31,30,31,30,31];
var today=new Date();
function setDate(dayfield,number) {
    dayfield.innerHTML = '';
    dayfield.options[0]=new Option('Day','Day');
    for (var i=1;i<=number; i++) { 
        if((today.getFullYear() == $("#yeardropdown").val()) && (today.getMonth()+1 == $("#monthdropdown").val())) { 
            if(today.getDate() >= i) {
                if(i == selectedDate) { 
                    dayfield.options[i] = new Option(daytext[i], daytext[i], true, true);
                }else {  
                    dayfield.options[i]=new Option(daytext[i], daytext[i]);
                }
            }
        } else {
            if(i == selectedDate) { 
                dayfield.options[i] = new Option(daytext[i], daytext[i], true, true);
            }else {  
                dayfield.options[i]=new Option(daytext[i], daytext[i]);
            }
        }
    }
}

function setMonth(monthfield,number) {
    monthfield.innerHTML = '';
    if(number=='')
        number = 12;
    monthfield.options[0]= new Option('Month','Month');
    for (var m=1; m<=number; m++) {
        if(m == selectedMonth) {
            monthfield.options[m]=new Option(monthtext[m], monthtext[m], true, true)
        }else {
            monthfield.options[m]=new Option(monthtext[m], monthtext[m])
        }
    }
}

function setYear(yearfield) {
    var thisyear=today.getFullYear()
    var i = 1;
    var j = 0;
    yearfield.options[0]=new Option('Year','Year');
    for (var y=thisyear; y>=1905; y--){
        if(y == selectedYear) {
            yearfield.options[i++]=new Option(y, y, true, true);	
        }else {
            yearfield.options[i++]=new Option(y, y);	
        }
        if(selectedYear == y){
            j = i-1;
        }
    }
}

function populatedropdown(dayfield, monthfield, yearfield){
    var dayfield=document.getElementById(dayfield)
    var monthfield=document.getElementById(monthfield)
    var yearfield=document.getElementById(yearfield)
    if((today.getFullYear() == selectedYear) && (today.getMonth()+1 == selectedMonth)) { 
        setDate(dayfield, today.getDate()); 
        setMonth(monthfield, selectedMonth); 
        setYear(yearfield);
    } else {
        setDate(dayfield,31); 
        setMonth(monthfield, 12); 
        setYear(yearfield);
    }
}

function updateDate(thisone,type) {
    var dayfield=document.getElementById("daydropdown");
    var monthfield=document.getElementById("monthdropdown");
    var yearfield=document.getElementById("yeardropdown");
    
    var year = yearfield.value;
    var leapYear = year%4;
    
    if(type == 'month') {
        var index = monthtext.indexOf(thisone); //alert(index);
        if(leapYear == 0) {
           var number = daysLeap[index];
           setDate(dayfield,number); 
        } else {
           var number = days[index];
           setDate(dayfield,number); 
        }
           selectedMonth = index;
    }
    
    if(type == 'year') {
        if(today.getFullYear() == thisone) {
            setMonth(monthfield,today.getMonth()+1); 
        } else { 
            if($("#monthdropdown").val() == "") {
                selectedMonth = $("#monthdropdown").val(); 
            } 
            setMonth(monthfield, '');             
        }
        var index = monthtext.indexOf(monthfield.value);
        if(leapYear == 0) { 
           var number = daysLeap[index];
           setDate(dayfield,number); 
        } else { 
            var number = days[index];
            if($("#monthdropdown").val() == "") {
                selectedDate = $("#monthdropdown").val();
            }
            setDate(dayfield,number); 
        }
    }
    if(type == 'day') {
        selectedDate = thisone;	
    }
}