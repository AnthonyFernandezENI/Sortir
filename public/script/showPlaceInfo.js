function afficherLieux(){
    let lieuxFound;
    let choixVille = document.getElementById('ville').value;
    let choixLieux = document.getElementById('places_to_go').value;
    document.getElementById('places_to_go').length = 0;
    let lieux = document.getElementById('hidden-degeu').value;
    let lieuxObj = JSON.parse(lieux);
    for (let i = 0; i < lieuxObj.length; i++){
        if(lieuxObj[i].ville.nom == choixVille){
            lieuxFound = lieuxObj[i];
            console.log(lieuxFound);
            document.getElementById('places_to_go').appendChild(new Option(lieuxFound.nom, lieuxFound.nom));
        }
    }
    if(lieuxFound != null){
        document.getElementById("rue").innerText = lieuxFound.rue;
        document.getElementById("longitude").innerText = lieuxFound.longitude;
        document.getElementById("latitude").innerText = lieuxFound.latitude;
        // document.getElementById("villeNom").innerText = lieuxFound.ville.nom;
        document.getElementById("villeCodePostal").innerText = lieuxFound.ville.codePostal;
    }
}

function afficherLieuxInfos() {
    // console.log(document.getElementById('places_to_go').value);
    let trouver;
    let choix = document.getElementById('places_to_go').value;
    let lieux = document.getElementById('hidden-degeu').value;
    let lieuxObj = JSON.parse(lieux);
    for (i = 0; i < lieuxObj.length; i++){
        if(lieuxObj[i].nom == choix){
            trouver = lieuxObj[i];
        }
    }
    if(trouver){
        // document.getElementById("nom").innerText = trouver.nom;
        document.getElementById("rue").innerText = trouver.rue;
        document.getElementById("longitude").innerText = trouver.longitude;
        document.getElementById("latitude").innerText = trouver.latitude;
        // document.getElementById("villeNom").innerText = trouver.ville.nom;
        document.getElementById("villeCodePostal").innerText = trouver.ville.codePostal;
    }
}
window.onload = afficherLieux();
window.onload = afficherLieuxInfos();
