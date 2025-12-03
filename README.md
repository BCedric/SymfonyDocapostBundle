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

### Service DocapostFast

| Fonction                                                                                      | Description                                                                               |
| --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| getSignInfo($documentiId)                                                                     | Retourne les infos sur la signature d'un document (message de rejet, nb de signatures...) |
| exportUsersData()                                                                             | Retourne tous les utilisateurs inscrits sur Docapost (données brutes de Docapost)         |
| getUsers():                                                                                   | Retourne les utilisateurs inscrits sur Docapost                                           |
| getUsersCertificate():                                                                        | Retourne les utilisateurs ayant un certificat RGS\*\*                                     |
| delete($documentId)                                                                           | Fonction permettant de supprimer un document                                              |
| getRefusalMessage($documentId)                                                                | Fonction qui renvoie le message de refus d'un document                                    |
| getFdc($documentId)                                                                           | Fonction qui retourne le contenu de la fiche de circulation                               |
| getSmsUrl($documentId)                                                                        | Fonction qui retourne le lien de de la prochaine signature OTP                            |
| history($documentId)                                                                          | Retourne l'historique de signatures d'un document                                         |
| dynamicCircuit($document, $steps, $OTPSteps, $emailDestinataire = "", $comment = "")          | Permet d'envoyer un document dans un circuit dynamique                                    |
| uploadDocument($document, $label, $comment = "", $emailDestinataires = [], $circuitId = null) | Envoi un document dans un circuit de signature                                            |
| downloadDocument($id)                                                                         | Permet de récupérer le contenu d'un document                                              |
| archive($documentId, $dir = null)                                                             | Archive un document dans le dossier définit dans les paramètres                           |

#### Utilisation de la fonction dynamicCircuit

La fonction dynamicCircuit permet d'envoyer un document dans un circuit dynamique, avec en paramètre :
- $document : le chemin vers le fichier à envoyer
- $steps : les étapes du circuit de signature, sous la forme suivante :
```
    [
        ["step" => "signature", "members" => ["email1@test.fr", "email2@test.fr]],
        ["step" => "visa", "members" => ["email1@test.fr", "email2@test.fr]],
        ["step" => "OTPSignature"]

    ]
```


- $otpSteps : Le paramètrave des étapes OTP :

```
    [
        [
            "email" => "email@test.fr"
            "firstname" => "Prénom"
            "lastname" => "Nom"
        ],
        [
            "email" => "email2@test.fr"
            "firstname" => "Prénom2"
            "lastname" => "Nom2"
        ],
    ]
```
- emailDestinataire: la liste des emails qui recevront le document signé sous la forme d'un tableau ou d'une chaine de caractères (les mails doivent être séparés par un ";")

#### Utilisation de la fonction uploadDocument
La fonction dynamicCircuit permet d'envoyer un document dans un circuit statique, avec en paramètre :
- $document : le chemin vers le fichier à envoyer
- $label : Un label permettant de classer le document
- $comment : un commentaire (par défaut '')
- $emailDestinataires : la liste des emails qui recevront le document signé sous la forme d'un tableau ou d'une chaine de caractères (les mails doivent être séparés par un ";")
- $circuitId : l'id du circuit à utiliser. Si non renseigné, l'id utilisé est celui renseigné dans la configuration du package.

### API

Utilisation de l'API : Dans le fichier `config/routes/annotations.yaml`, ajouter :

```
    bcedric_docapost:
        resource: "../../vendor/bcedric/docapost/src/Controller/DocapostController.php"
        type: attribute
        prefix: /mon_prefix
```

| URL                                   | Description                                                                                                                                 | Méthode | Paramètres |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ------- | ---------- |
| /docapost/users                       | Retourne la liste des utilisateurs inscrits sur Docapost                                                                                    | GET     | filename   |
| /docapost/certif-users                | Retourne la liste des utilisateurs qui possède une certification RGS\*\*                                                                    | GET     | filename   |
| /download/{docapost_id}               | Renvoie le document dans son état actuel                                                                                                    | GET     | filename   |
| /getFdc/{docapost_id}                 | Renvoie la fiche de circualtion du document                                                                                                 | GET     | filename   |
| /downloadDocumentAndFDC/{docapost_id} | Renvoie la fusion de la fiche de circualtion et du document (Cette fonction utilise le package ghostscrit)                                  | GET     | filename   |
| /downloadProofFile/{docapost_id}      | Renvoie le dossier de preuve                                                                                                                | GET     | filename   |
| /infos/{docapost_id}                  | Retourne un tableau d'informations concernant la signature du document (message de refus, URL de la prochaine signature OTP, historique...) | GET     |            |
