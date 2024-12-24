-- SQLBook: Code
DROP SCHEMA IF EXISTS biblio CASCADE;
CREATE SCHEMA biblio;
SET SCHEMA 'biblio';

CREATE TABLE _author (
    authId varchar(10)  PRIMARY KEY,
    authName varchar(256) UNIQUE
);

CREATE TABLE _doc (
    docId SERIAL PRIMARY KEY,
    title varchar(256) UNIQUE,
    doi varchar(256),
    pYear int,
    minPages int,
    maxPages int,
    venue varchar(5),
    ee varchar(256),
    docUrl varchar(256)
);

CREATE TABLE _was_written_by (
    authId varchar(10)  REFERENCES _author(authId),
    docId int           REFERENCES _doc(docId)
);

CREATE TABLE _conference (
    conferenceId SERIAL PRIMARY KEY REFERENCES _doc(docId)
);

CREATE TABLE _article (
    articleId int       PRIMARY KEY REFERENCES _doc(docId),
    volume int,
    venueNumber int
);

CREATE TABLE _event (
    eventId SERIAL PRIMARY KEY,
    eventName varchar(256)
);

CREATE TABLE _continent (
    continentId SERIAL PRIMARY KEY,
    nameContinent varchar(256) UNIQUE
);

CREATE TABLE _country (
    nameCountry varchar(256)PRIMARY KEY,
    continentId int REFERENCES _continent(continentId)
);

CREATE TABLE _city (
    nameCity varchar(256) ,
    nameCountry varchar(256)  REFERENCES _country(nameCountry),
    lat Decimal(9,6),
    lng Decimal(9,6),
    constraint PK_city PRIMARY KEY(nameCity, nameCountry)
    
);

 
CREATE TABLE _edition (
    conferenceId int REFERENCES _conference(conferenceId),
    eventId int REFERENCES _event(eventId),
    nameCity varchar(256),
    nameCountry varchar(256),
    FOREIGN KEY (nameCity, nameCountry) REFERENCES _city(nameCity, nameCountry)
);

CREATE TABLE Conferences (
    acronym VARCHAR(255) PRIMARY KEY,
    conference_name VARCHAR(255)
);

CREATE TABLE Editions (
    edition_id SERIAL PRIMARY KEY,
    conference_acronym VARCHAR(255) REFERENCES Conferences(acronym), 
    years INT,
    nameCity VARCHAR(256),
    nameCountry VARCHAR(256),
    FOREIGN KEY (nameCity, nameCountry) REFERENCES _city(nameCity, nameCountry)
);

CREATE TABLE _conf (
    acronym varchar(20),
    titre varchar(256),
    rankC varchar (30)
);

WbImport -file="Countries-Continents.csv"
         -type=text
         -table=_continent
         -encoding="UTF-8"
         -header=true
         -decode=false
         -dateFormat="yyyy-MM-dd"
         -timestampFormat="yyyy-MM-dd HH:mm:ss"
         -delimiter=','
         -decimal=.
         -fileColumns=$wb_skip$,namecontinent,continentid
         -quoteCharEscaping=none
         -ignoreIdentityColumns=false
         -deleteTarget=false
         -continueOnError=true;


WbImport -file="Countries-Continents.csv"
         -type=text
         -table=_country
         -encoding="UTF-8"
         -header=true
         -decode=false
         -dateFormat="yyyy-MM-dd"
         -timestampFormat="yyyy-MM-dd HH:mm:ss"
         -delimiter=','
         -decimal=.
         -fileColumns=namecountry,$wb_skip$,continentid
         -quoteCharEscaping=none
         -ignoreIdentityColumns=false
         -deleteTarget=false
         -continueOnError=true;

WbImport -file=worldcities.csv
         -type=text
         -table=_city
         -encoding="UTF-8"
         -header=true
         -decode=false
         -dateFormat="yyyy-MM-dd"
         -timestampFormat="yyyy-MM-dd HH:mm:ss"
         -delimiter=','
         -decimal=.
         -fileColumns=namecity,lat,lng,namecountry
         -quoteCharEscaping=none
         -ignoreIdentityColumns=false
         -deleteTarget=false
         -continueOnError=true;

WbImport -file=CORE.csv
         -type=text
         -table=_conf
         -encoding="UTF-8"
         -header=true
         -decode=false
         -dateFormat="yyyy-MM-dd"
         -timestampFormat="yyyy-MM-dd HH:mm:ss"
         -delimiter=','
         -decimal=.
         -fileColumns=titre,acronym,rankc
         -quoteCharEscaping=none
         -ignoreIdentityColumns=false
         -deleteTarget=false
         -continueOnError=true;
