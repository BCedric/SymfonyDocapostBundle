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