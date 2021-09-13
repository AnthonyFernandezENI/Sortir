function afficherLieux() {
    // console.log(document.getElementById('places_to_go').value);
    choix = document.getElementById('places_to_go').value;
    lieux = document.getElementById('hidden-degeu').value;
    lieuxObj = JSON.parse(lieux);
    for (i = 0; i < lieuxObj.length; i++){
        if(lieuxObj[i].nom == choix){
            trouver = lieuxObj[i];
        }
    }
    if(trouver){
        document.getElementById("nom").innerText = trouver.nom;
        document.getElementById("rue").innerText = trouver.rue;
        document.getElementById("longitude").innerText = trouver.longitude;
        document.getElementById("latitude").innerText = trouver.latitude;
        document.getElementById("villeNom").innerText = trouver.ville.nom;
        document.getElementById("villeCodePostal").innerText = trouver.ville.codePostal;
    }
}

window.onload = afficherLieux();