!function(s){
//show next function
function t(t,a,o){""!=s("#"+a).val()?(s("html, body").animate({scrollTop:s("#"+t).offset().top-s(".header_top_bottom_holder").height()},600),s("#"+t).hasClass("hidden")&&s("#"+t).removeClass("hidden")):(s("#error").html(o),s("#error").addClass("active")),setTimeout(function(){s("#error").html(""),s("#error").removeClass("active")},5e3)}
//on measurement input
//calculate Sqm
function o(t,a,o){var e=(t/1e3+o/1e3)*(o/1e3+a/1e3)*2;return(e=e.toFixed(2))<.3&&(e=.3),console.log("Calculate Sqm "+e),e}
//calculate price function using ajax
function e(){
// preventDefault();
var l=s("#bb-sqm").val()*s("#bb-qty").val();console.log("single box sq metre : "+s("#bb-sqm").val()),s.ajax({url:bbprice.ajax_url,beforeSend:function(){s("#completion-col").append('<span class="loading-cover"><i class="fa fa-spin fa-refresh"></i></span>')},type:"post",data:{action:"calc_price",qty:s("#bb-qty").val(),weight:s("#bb-thickness").val(),singleboxsqm:s("#bb-sqm").val(),sqm:l},success:function(t){var a=JSON.parse(t);console.log(a),s("#completion-col .loading-cover").remove(),s("#roll-count").html(a.free_rolls),s("#delivery-alert").addClass("alert-info"),l<200?s("#delivery-alert").html("Please note: Lead time for your order will be <strong>5-7 days.</strong>"):s("#delivery-alert").html("Please note: Lead time for your order will be <strong>7-10 days.</strong>");
//$('#total-price').html("&pound;"+Number(responseData.price).toFixed(2));
var o=Number(a.price).toFixed(2),e=o*s("#bb-qty").val();s("#total-price").html("&pound;"+o+" per box<br/><small>Total order value: &pound"+Number(e).toFixed(2)+"</small>"),s("#bb-price").val(o),100<e?s("#delivery-result").html("& <strong>FREE DELIVERY</strong>"):s("#delivery-result").html("")}}),console.log("Total Sqm : "+l)}var l,n;
//on hover show tooltip
s(".bb").on("mouseenter",".option-img",function(){var t=s(this).attr("data-tooltip"),a=s(this).attr("data-tooldir");s(this).append('<span class="option-tooltip '+a+'">'+t+"</span>")}),
//on hover show tooltip
s(".bb").on("mouseleave",".option-img",function(){s(".option-tooltip",this).remove()}),
//show next section on click
s(".bb").on("click",".show-next",function(){t(s(this).attr("data-target"),s(this).attr("data-val"),s(this).attr("data-error")),s(this).hasClass("calculate")&&e()}),
//on select thickness insert value to input
s(".bb").on("click",".option-col",function(){var t=s(this).attr("data-option-val");s("#bb-thickness").val(t)&&(s(".option-col").hasClass("active")&&s(".option-col").removeClass("active"),s(this).addClass("active")),""!=s("#bb-sqm").val()&&""!=s("#bb-qty").val()&&""!=s("#bb-thickness").val()&&e()}),
//on qty change, adjust form input
s(".bb").on("change","#bb-quantity",function(){"contact"==s(this).val()?(console.log("contact"),0==s("#qty-calc-btn").hasClass("fwd-to-contact")&&s("#qty-calc-btn").addClass("fwd-to-contact"),s("#qty-calc-btn").hasClass("show-next")&&s("#qty-calc-btn").removeClass("show-next"),s("#qty-calc-btn").html("Contact us")):(s("#qty-calc-btn").hasClass("fwd-to-contact")&&s("#qty-calc-btn").removeClass("fwd-to-contact"),0==s("#qty-calc-btn").hasClass("show-next")&&s("#qty-calc-btn").addClass("show-next"),s("#qty-calc-btn").html("Calculate"),s("#bb-qty").val(s(this).val()),e())}),s(".bb").on("click",".fwd-to-contact",function(t){t.preventDefault(),window.location.href="/contact-us"}),s(".bb").on("keyup",".dimension-input",function(t){if(l=!1,s(this).is("#bb-length")?""!=s(this).val()?s(".length").html(s(this).val()+"mm"):s(".length").html(""):s(this).is("#bb-height")?""!=s(this).val()?s(".height").html(s(this).val()+"mm"):s(".height").html(""):s(this).is("#bb-width")&&(""!=s(this).val()?s(".width").html(s(this).val()+"mm"):s(".width").html("")),""!=s("#bb-length").val()&&""!=s("#bb-height").val()&&""!=s("#bb-width").val()){if(
//check that all input values are between 100 and 600
s(".dimension-input").each(function(){console.log(Number(s(this).val())),(Number(s(this).val())<100||600<Number(s(this).val()))&&(l=!0,n="Dimensions should be greater than 100 and less than 600.")}),Number(s("#bb-length").val())<Number(s("#bb-width").val())&&(l=!0,n='Your length can\'t be less than your width. <a href="/contact-us">Contact us</a>'),l)s("#error").html(n),s("#error").addClass("active"),s("#bb-sqm").val("");else{var a=o(s("#bb-length").val(),s("#bb-height").val(),s("#bb-width").val());s("#error").html(""),s("#error").removeClass("active"),s("#bb-sqm").val(a)}""!=s("#bb-sqm").val()&&""!=s("#bb-qty").val()&&""!=s("#bb-thickness").val()&&e(),console.log(l)}}),
//reset button
s(".bb").on("click","#btn-reset",function(){s("html, body").animate({scrollTop:s("#bb-thickness").offset().top-s(".header_top_bottom_holder").height()},600),s('.bb[data-status="hide"]').each(function(){s(this).addClass("hidden")})}),
//on add to cart button create new variation and add new product var to cart
s("#completion-col").on("click","#add-to-cart",function(t){t.preventDefault(),console.log("Create Variation");
// preventDefault();
var a=s("#bb-sqm").val()*s("#bb-qty").val();s.ajax({url:createvar.ajax_url,beforeSend:function(){s("#add-to-cart").prop("disabled",!0),s("#add-to-cart").html('<i class="fa fa-spin fa-refresh"></i>')},type:"post",data:{action:"create_var",qty:s("#bb-qty").val(),weight:s("#bb-thickness").val(),height:s("#bb-height").val(),width:s("#bb-width").val(),length:s("#bb-length").val(),price:s("#bb-price").val(),sqm:a},success:function(t){var a=JSON.parse(t);console.log(a),s("#add-to-cart").html("Added to cart!"),setTimeout(function(){window.location.replace(window.location.protocol+"//"+window.location.hostname+"/cart")},500)}}),console.log(a)})}(jQuery);