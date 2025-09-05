# Project Scope: Schema at Scale Setup 2025 Q3

Jun 2, 2025  
Prepared by: [Sarah Johnson](mailto:sarah.johnson@amfmhealthcare.com)

## Overview

Make schema setup that will work at scale. Schema coming in from byline module is already present and works great. This will be in addition to the existing schema from the byline module.

## Goals

* Improve schema markup around the sites that will improve visibility in search engines and LLM/AIs  
* Improve EEAT

## Details & References

[Digital AMFM Project Planning Dashboard](https://docs.google.com/spreadsheets/d/16Lzs-gYyTAY4rkPi-dvzcIHcmYDVvqDyzxnnmDG0dkY/edit?gid=539273865#gid=539273865)  
Mia’s Master Schema doc [Core Schema Types for a Mental Health Treatment Page](https://docs.google.com/document/d/1zOXPoTgsicXIh8sfz0uQRH0R8mrpHOEfs-0WVZnwYO8/edit?tab=t.0)  
[https://validator.schema.org/](https://validator.schema.org/)

`ML_JSON = website cached version of json from Website Locations Master Sheet`

## Requirements

* Add ACF Field to hold a custom schema that will be available on every page.  
  * Ideally we want this accessible on the main wp edit page and IF possible the quick edit part of the page  
  * This will get a pile of JSON code  
  * This JSON code may have shortcodes in it to add parts of it that we need to dynamically create and insert (examples below)  
    * Medical Organization \- this will be standard for each brand  
      * Medical Clinic \- this will respect brand AND location filter parameter with matching values to be looped through to produce schema pulled from master location JSON or the filtered JSONs  
      * Ideal shortcode will be something like `{{location}}` with   
        * brand filtering for matches from `{{ML_JSON.(Internal) Shortname}}`  
        * region filtering for matches from `{{ML_JSON.Region}}`  
        * and/or state filtering for matches `{{ML_JSON.State}}`  
      * Values to pull in from master location JSON through loop

```json
.... snipped see example ...
{
         "@type": "MedicalClinic",
          "name": {{ML_JSON.Business name}},
          "address": {
            "@type": "PostalAddress",
            "streetAddress": {{ML_JSON.Street}} {{ML_JSON.Unit}},
            "addressLocality": {{ML_JSON.City}},
            "addressRegion": {{ML_JSON.State}},
            "postalCode": {{ML_JSON.Zipcode}},
            "telephone": {{ML_JSON.GMB_Phone}},
            "addressCountry": "US"
          }
        },
.... snipped ...

```

    * Breadcrumbs \- same as whats being added for bylines but ONLY that specific part

### Process to add schema to front end of websites

* If Custom Schema ACF Field has custom schema  
  * Evaluate custom schema field for any shortcode  
  * If shortcode parse appropriate schema by   
    * using the master location JSON to build medical organization and medical clinic setup  
      * Needs to respect a shortcode parameter of region or state   
    * Using breadcrumbs setup same as what the byline module schema is building for its main page type  
  * Add this schema setup to the schema produced by byline module and push to front end of website  
* Else push ONLY schema produced by byline module

## Examples

### ACF Custom Schema Field Setup for  MEDICAL CONDITION EXAMPLE SCHEMA TYPE 3 for DEPRESSION with multiple locations 

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc"}}
    {
      "@type": "MedicalCondition",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#condition",
      "name": "Intermittent Explosive Disorder (IED)",
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",
      "description": "A behavioral disorder marked by recurrent, impulsive aggressive outbursts that are disproportionate to stressors.",
      "signOrSymptom": [
        { "@type": "MedicalSignOrSymptom", "name": "Recurrent aggressive outbursts" },
        { "@type": "MedicalSignOrSymptom", "name": "Irritability" },
        { "@type": "MedicalSignOrSymptom", "name": "Impulsivity" }
      ],
      "riskFactor": [
        { "@type": "MedicalRiskFactor", "name": "History of trauma" },
        { "@type": "MedicalRiskFactor", "name": "Early life adversity" },
        { "@type": "MedicalRiskFactor", "name": "Substance misuse" }
      ],
      "typicalTest": [
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5-TR criteria)" },
        { "@type": "MedicalTest", "name": "Psychological evaluation / standardized anger assessments" }
      ],
      "differentialDiagnosis": [
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Borderline personality disorder" } },
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Antisocial personality disorder" } }
      ],
      "possibleTreatment": [
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },
        {
          "@type": "MedicalTherapy",
          "name": "Medication management",
          "drug": { "@type": "Drug", "name": "Selective serotonin reuptake inhibitors (SSRIs)" }
        }
      ],
      "primaryPrevention": [
        { "@type": "MedicalTherapy", "name": "Anger management education" },
        { "@type": "MedicalTherapy", "name": "Stress management and coping skills training" }
      ],
      "secondaryPrevention": [
        { "@type": "MedicalTherapy", "name": "Relapse prevention plan and follow-up care" },
        { "@type": "MedicalTherapy", "name": "Booster CBT/skills sessions" }
      ],
      "epidemiology": "Onset typically in adolescence; more common in males."
    },
    {
      "@type": "Offer",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#offer",
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",
      "availability": "https://schema.org/InStock",
      "itemOffered": {
        "@type": "Service",
        "serviceType": "Anger management & Intermittent Explosive Disorder treatment",
        "provider": {
          "@id": "https://missionconnectionhealthcare.com/#org"
        }
      }
    },
    {
      "@type": "FAQPage",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is Intermittent Explosive Disorder (IED)?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "IED is a mental health condition characterized by sudden, repeated episodes of impulsive, aggressive, or violent behavior."
          }
        },
        {
          "@type": "Question",
          "name": "How is IED treated?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Treatment for IED may include therapy such as CBT or DBT, medication like SSRIs, and structured anger management programs."
          }
        },
        {
          "@type": "Question",
          "name": "Who is most at risk for IED?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "IED often begins in adolescence and is more common in males, particularly those with a history of trauma or early adversity."
          }
        }
      ]
    }
  ]
}


```

### Schema created based on the shortcode being read from the ACF field `{{location brand="mc"}}` as seen above

```json
 {
      "@type": "MedicalOrganization",
      "@id": "https://missionconnectionhealthcare.com/#org",
      "name": "Mission Connection Healthcare",
      "url": "https://missionconnectionhealthcare.com/",
      "logo": "https://missionconnectionhealthcare.com/wp-content/uploads/2024/03/logo.svg",
      "description": "Mission Connection Healthcare offers specialized mental health treatment services, including support for anger issues and Intermittent Explosive Disorder (IED).",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+1-555-555-5555",
        "areaServed": "US",
        "availableLanguage": "English"
      },
      "department": [
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "2900 S. Quincy St #810",
            "addressLocality": "Arlington",
            "addressRegion": "VA",
            "postalCode": "22206",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "6900 East Green Lake Way N Suite G",
            "addressLocality": "Seattle",
            "addressRegion": "WA",
            "postalCode": "98115",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "11000 NE 33rd Pl #340",
            "addressLocality": "Bellevue",
            "addressRegion": "WA",
            "postalCode": "98004",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "30300 Rancho Viejo Rd Suite A",
            "addressLocality": "San Juan Capistrano",
            "addressRegion": "CA",
            "postalCode": "92675",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "7777 Leesburg Pike Suite 10N",
            "addressLocality": "Falls Church",
            "addressRegion": "VA",
            "postalCode": "22043",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        }
      ]
    },


```

### Rendered Schema MEDICAL CONDITION EXAMPLE SCHEMA TYPE 3 for DEPRESSION with multiple locations 

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "MedicalOrganization",
      "@id": "https://missionconnectionhealthcare.com/#org",
      "name": "Mission Connection Healthcare",
      "url": "https://missionconnectionhealthcare.com/",
      "logo": "https://missionconnectionhealthcare.com/wp-content/uploads/2024/03/logo.svg",
      "description": "Mission Connection Healthcare offers specialized mental health treatment services, including support for anger issues and Intermittent Explosive Disorder (IED).",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+1-555-555-5555",
        "areaServed": "US",
        "availableLanguage": "English"
      },
      "department": [
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "2900 S. Quincy St #810",
            "addressLocality": "Arlington",
            "addressRegion": "VA",
            "postalCode": "22206",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "6900 East Green Lake Way N Suite G",
            "addressLocality": "Seattle",
            "addressRegion": "WA",
            "postalCode": "98115",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "11000 NE 33rd Pl #340",
            "addressLocality": "Bellevue",
            "addressRegion": "WA",
            "postalCode": "98004",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "30300 Rancho Viejo Rd Suite A",
            "addressLocality": "San Juan Capistrano",
            "addressRegion": "CA",
            "postalCode": "92675",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        },
        {
          "@type": "MedicalClinic",
          "name": "Mission Connection",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "7777 Leesburg Pike Suite 10N",
            "addressLocality": "Falls Church",
            "addressRegion": "VA",
            "postalCode": "22043",
            "telephone": "+1-555-555-5555",
            "addressCountry": "US"
          }
        }
      ]
    },
    {
      "@type": "MedicalCondition",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#condition",
      "name": "Intermittent Explosive Disorder (IED)",
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",
      "description": "A behavioral disorder marked by recurrent, impulsive aggressive outbursts that are disproportionate to stressors.",
      "signOrSymptom": [
        { "@type": "MedicalSignOrSymptom", "name": "Recurrent aggressive outbursts" },
        { "@type": "MedicalSignOrSymptom", "name": "Irritability" },
        { "@type": "MedicalSignOrSymptom", "name": "Impulsivity" }
      ],
      "riskFactor": [
        { "@type": "MedicalRiskFactor", "name": "History of trauma" },
        { "@type": "MedicalRiskFactor", "name": "Early life adversity" },
        { "@type": "MedicalRiskFactor", "name": "Substance misuse" }
      ],
      "typicalTest": [
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5-TR criteria)" },
        { "@type": "MedicalTest", "name": "Psychological evaluation / standardized anger assessments" }
      ],
      "differentialDiagnosis": [
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Borderline personality disorder" } },
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Antisocial personality disorder" } }
      ],
      "possibleTreatment": [
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },
        {
          "@type": "MedicalTherapy",
          "name": "Medication management",
          "drug": { "@type": "Drug", "name": "Selective serotonin reuptake inhibitors (SSRIs)" }
        }
      ],
      "primaryPrevention": [
        { "@type": "MedicalTherapy", "name": "Anger management education" },
        { "@type": "MedicalTherapy", "name": "Stress management and coping skills training" }
      ],
      "secondaryPrevention": [
        { "@type": "MedicalTherapy", "name": "Relapse prevention plan and follow-up care" },
        { "@type": "MedicalTherapy", "name": "Booster CBT/skills sessions" }
      ],
      "epidemiology": "Onset typically in adolescence; more common in males."
    },
    {
      "@type": "Offer",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#offer",
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",
      "availability": "https://schema.org/InStock",
      "itemOffered": {
        "@type": "Service",
        "serviceType": "Anger management & Intermittent Explosive Disorder treatment",
        "provider": {
          "@id": "https://missionconnectionhealthcare.com/#org"
        }
      }
    },
    {
      "@type": "FAQPage",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is Intermittent Explosive Disorder (IED)?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "IED is a mental health condition characterized by sudden, repeated episodes of impulsive, aggressive, or violent behavior."
          }
        },
        {
          "@type": "Question",
          "name": "How is IED treated?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Treatment for IED may include therapy such as CBT or DBT, medication like SSRIs, and structured anger management programs."
          }
        },
        {
          "@type": "Question",
          "name": "Who is most at risk for IED?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "IED often begins in adolescence and is more common in males, particularly those with a history of trauma or early adversity."
          }
        }
      ]
    }
  ]
}


```

## Adrian Notes or Questions

