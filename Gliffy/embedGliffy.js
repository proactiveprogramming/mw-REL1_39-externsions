function embedGliffy(did) {
window.onload = function() {
document.getElementById("gliffy-" + did).innerHTML = "<img class='gliffyDiagram' src='http://www.gliffy.com/go/view/" + did + ".png' /><br><a href='https://www.gliffy.com/go/html5/" + did + "' target='_blank'><div style='border:1px solid #333333; border-radius:3px; font-family:Arial; font-size:10pt; color:#222222; background-color:#f2f2f2; padding:2px 5px; margin:5px; font-weight:bold; display:inline-block;'>Edit with <img src='/extensions/Gliffy/Gliffy-Logo.png' width='50px' style='margin:0' /></div></a>";
}}