# Symfony Docapost Bundle

Bundle de connexion d'une application symfony vers le parapheur Docapost Fast

## Installation

- Lancer la commande `composer require bcedric/docapost`
- Ajouter le bundle dans `config/bundle.php` :

```
    <?php

    return [
        // ...
        BCedric\DocapostBundle\BCedricDocapostBundle::class => ['all' => true],
    ];

```

- créer le fichier `config/packages/b_cedric_docapost.yaml` avec le contenu suivant :

```
    b_cedric_docapost:
        pem_file: "path/to/certif.pem"
        url: "https://parapheur.url.fr/parapheur-ws/rest/v1/documents/"
        siren: "siren"
        circuitId: "circuit"
        archives_dir: mon/repertoire/archives
        proxy_url: "http://mon.proxy"

```

- Utilisation des entitées du package (DocapostUser) : Ajouter dans le fichier `config/packages/doctrine.yaml`:

```
doctrine:
    #...
    orm:
        #...
        mappings:
            #...
            BCedricDocapostBundle:
                is_bundle: true
                type: attribute
                alias: DocapostBundle
```

- Utilisation de l'API : Dans le fichier `config/routes/annotations.yaml`, ajouter :

```
    bcedric_docapost:
        resource: "../../vendor/bcedric/docapost/src/Controller/DocapostController.php"
        type: attribute
        prefix: /mon_prefix
```

### API

| URL                                   | Description                                                                                                                                 | Méthode |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ------- |
| /docapost/users                       | Retourne la liste des utilisateurs inscrits sur Docapost                                                                                    | GET     |
| /docapost/certif-users                | Retourne la liste des utilisateurs qui possède une certification RGS\*\*                                                                    | GET     |
| /download/{docapost_id}               | Renvoie le document dans son état actuel                                                                                                    | GET     |
| /getFdc/{docapost_id}                 | Renvoie la fiche de circualtion du document                                                                                                 | GET     |
| /downloadDocumentAndFDC/{docapost_id} | Renvoie la fusion de la fiche de circualtion et du document (Cette fonction utilise le package ghostscrit)                                  | GET     |
| /downloadProofFile/{docapost_id}      | Renvoie le dossier de preuve                                                                                                                | GET     |
| /infos/{docapost_id}                  | Retourne un tableau d'informations concernant la signature du document (message de refus, URL de la prochaine signature OTP, historique...) | GET     |
