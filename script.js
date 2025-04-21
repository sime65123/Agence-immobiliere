//définir la la largeur de la sidebar a 100vw  et faire un overflow hidden

function openNav(){
    document.getElementById("mySidebar").style.width="100vw";
    document.querySelector("body").style.overflow= "hidden"

}

//définir la la largeur de la sidebar a 0  et faire un overflow auto

function closeNav(){
    document.getElementById("mySidebar").style.width="0";
    document.querySelector("body").style.overflow= "auto"

}