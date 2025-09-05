**Constant elements**

The acf field that will hold schema will need shortcode or similar that engineering will read to parse the values in for this before rendering on the frontend. These shortcodes will need to be inserted into the schema being built by the team.

* Medical Organization  
  * Medical Clinic  
* Breadcrumbs

Need exact examples for how this schema should be rendered. Process

* Byline module passes in article, author, medicalWebPage  
* Custom Schema ACF Field has custom schema  
  * Evaluate custom schema field for any shortcode  
  * If shortcode parse appropriate schema by   
    * using the master location json to build medical organization and medical clinic setup  
      * Needs to respect a shortcode parameter of region or state   
    * Using breadcrumbs setup same as what the byline module schema is building for its main page type

Mia \- add anything else that is constant value. 

Engineering notes: [Schema at Scale Setup 2025 Q3](https://docs.google.com/document/d/1rUMTHfU35Fceem3G8DtyhZzTksWxL8yNB2ZiCYSmDxA/edit?tab=t.0#heading=h.ycq7lulrv60l)

NOTE: Custom schema field json does NOT need to be wrapped in \<script\>\</script\> tags as we will add that after parsing it to push to front end

**Core Schema Types for a Mental Health Treatment Page**

* **MedicalCondition** : for example **Depression** (including subtypes like Major Depressive Disorder, Persistent Depressive Disorder).  
* **MedicalTherapy** : for treatment approaches (CBT, DBT, medication, residential care, inpatient, etc.).  
* **PsychologicalTreatment** : a subtype of *MedicalTherapy* specifically for therapy interventions.  
* **Drug** : if you mention antidepressants or other medications.  
* **Organization** : for the treatment center providing care.  
* **LocalBusiness / MedicalOrganization** : for a clinic, residential treatment center, or hospital.  
* **Hospital / MedicalClinic / Psychiatric** (if residential or inpatient).  
* **Person** : for psychiatrists, psychologists, or therapists listed on the page.  
* **FAQPage** : for common questions about depression treatment.  
* **HowTo** : if you outline step-by-step treatment processes or admissions process.  
* **Article / WebPage** : for general content structure.

**Supporting / Enrichment Schema Types**

* **MedicalWebPage** : specifies the content is medically focused.  
* **BreadcrumbList** : for navigation structure.  
* **Review / AggregateRating** : if you display reviews/testimonials about treatment.  
* **EducationalOccupationalProgram** : if there are therapy programs, residential programs, or educational workshops.  
* **Event** : for group therapy sessions, workshops, or webinars.  
* **Service** : describing “Depression Treatment” as a service offering.  
* **Offer** : if treatment programs or sessions have pricing (not always shown publicly).  
* **ContactPoint** : for phone numbers, admissions contact, or crisis hotline.  
* **PostalAddress** : linked under the treatment facility.  
* **OpeningHoursSpecification** : if it’s a clinic or facility with set hours.  
* **SpecialAnnouncement** : if you have COVID-19 or crisis-related updates.

**Condition-Specific Schema Types (Deep Medical Layer)**

* **MedicalGuideline** : if referencing best practices (e.g., APA, NIMH guidelines).  
* **MedicalTest** : if you reference screenings for depression.  
* **RiskFactor** : if discussing risk factors of depression.  
* **Symptom** : if listing symptoms of depression.  
* **AnatomicalStructure** : rarely needed, but could apply if mentioning brain regions affected.  
* **Cause** : underlying causes of depression.  
* **SideEffect** : for medications or therapies.  
* **DoseSchedule** : if listing medication dosage examples.  
* **MedicalIndication** : if a therapy is specifically indicated for depression.

**Priority for SEO & Rich Results**

If your main goal is **SEO \+ rich snippets**, I recommend prioritizing:

1. **MedicalCondition (Depression)**  
2. **MedicalTherapy / PsychologicalTreatment**  
3. **Organization / LocalBusiness (your facility)**  
4. **FAQPage**  
5. **MedicalWebPage**  
6. **BreadcrumbList**  
7. **Review / AggregateRating** (if applicable)

MEDICAL CONDITION SCHEMA EXAMPLE: [https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/](https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/) ![][image1]

{  
  "@context": "https://schema.org",  
  "@graph": \[  
    {  
      "@type": "MedicalWebPage",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#webpage",  
      "url": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/",  
      "name": "The Best Treatment Options for Depression",  
      "datePublished": "2025-06-04",  
      "author": { "@type": "Person", "name": "Emma Loker" },  
      "reviewedBy": { "@type": "Person", "name": "Ashley Pena" },  
      "publisher": { "@id": "https://missionconnectionhealthcare.com/\#org" },  
      "breadcrumb": {  
        "@type": "BreadcrumbList",  
        "itemListElement": \[  
          { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://missionconnectionhealthcare.com/" },  
          { "@type": "ListItem", "position": 2, "name": "Mental Health", "item": "https://missionconnectionhealthcare.com/mental-health/" },  
          { "@type": "ListItem", "position": 3, "name": "Depression", "item": "https://missionconnectionhealthcare.com/mental-health/depression/" },  
          { "@type": "ListItem", "position": 4, "name": "Treatment Approaches", "item": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/" }  
        \]  
      },  
      "about": \[  
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },  
        { "@type": "MedicalTherapy", "name": "Interpersonal Psychotherapy (IPT)" },  
        { "@type": "MedicalTherapy", "name": "Group Therapy" },  
        { "@type": "MedicalTherapy", "name": "Mindfulness-Based Approaches" },  
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },  
        { "@type": "MedicalTherapy", "name": "Behavioral Activation" },  
        { "@type": "MedicalTherapy", "name": "Teletherapy (Online Therapy)" }  
      \],  
      "mainEntity": {  
        "@type": "MedicalCondition",  
        "name": "Depression",  
        "alternateName": "Major Depressive Disorder",  
        "possibleTreatment": \[  
          { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },  
          { "@type": "MedicalTherapy", "name": "Interpersonal Psychotherapy (IPT)" },  
          { "@type": "MedicalTherapy", "name": "Group Therapy" },  
          { "@type": "MedicalTherapy", "name": "Mindfulness-Based Cognitive Therapy (MBCT)" },  
          { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },  
          { "@type": "MedicalTherapy", "name": "Behavioral Activation" },  
          { "@type": "MedicalTherapy", "name": "Teletherapy (Online Therapy)" }  
        \],  
        "drug": { "@type": "Drug", "name": "Antidepressant medications" }  
      }  
    },  
    {  
      "@type": "MedicalOrganization",  
      "@id": "https://missionconnectionhealthcare.com/\#org",  
      "name": "Mission Connection Healthcare",  
      "url": "https://missionconnectionhealthcare.com/",  
      "isAcceptingNewPatients": true,  
      "medicalSpecialty": "https://schema.org/Psychiatry",  
      "areaServed": \[  
        { "@type": "AdministrativeArea", "name": "California" },  
        { "@type": "AdministrativeArea", "name": "Virginia" },  
        { "@type": "AdministrativeArea", "name": "Washington" }  
      \],  
      "contactPoint": {  
        "@type": "ContactPoint",  
  
        "telephone": "+1-866-833-1822"  
      },  
      "makesOffer": {  
        "@type": "Offer",  
        "offeredBy": { "@id": "https://missionconnectionhealthcare.com/\#org" },  
        "itemOffered": {  
          "@type": "Service",  
          "name": "Depression Treatment",  
          "serviceType": "Mental Health Treatment",  
          "areaServed": \[  
            { "@type": "AdministrativeArea", "name": "California" },  
            { "@type": "AdministrativeArea", "name": "Virginia" },  
            { "@type": "AdministrativeArea", "name": "Washington" }  
          \],  
          "provider": { "@id": "https://missionconnectionhealthcare.com/\#org" }  
        }  
      }  
    },  
    {  
      "@type": "FAQPage",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#faq",  
      "mainEntity": \[  
        {  
          "@type": "Question",  
          "name": "What is the most common treatment for depression?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "The most common treatment for depression combines psychotherapy, such as Cognitive Behavioral Therapy (CBT), with antidepressant medications when appropriate."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Can depression be treated without medication?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Yes, many people benefit from therapy approaches like CBT, DBT, or mindfulness-based practices without the use of medication, although some cases may require both."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Does Mission Connection Healthcare offer online therapy for depression?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Yes, Mission Connection Healthcare offers teletherapy options so individuals can receive professional depression treatment support online."  
          }  
        }  
      \]  
    }  
  \]  
}

MEDICAL CONDITION EXAMPLE SCHEMA TYPE 2 for DEPRESSION: without multiple locations [https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/](https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/) 

![][image2]

\<script type="application/ld+json"\>  
{  
  "@context": "https://schema.org",  
  "@graph": \[  
    {  
      "@type": "MedicalOrganization",  
      "@id": "https://missionconnectionhealthcare.com/\#org",  
      "name": "Mission Connection Healthcare",  
      "url": "https://missionconnectionhealthcare.com/",  
      "logo": "https://missionconnectionhealthcare.com/wp-content/uploads/2024/03/logo.svg",  
      "description": "Mission Connection Healthcare offers specialized mental health treatment services, including support for depression.",  
      "contactPoint": {  
        "@type": "ContactPoint",  
        "telephone": "+1-555-555-5555",  
  
        "areaServed": "US",  
        "availableLanguage": "English"  
      }  
    },  
    {  
      "@type": "MedicalCondition",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#condition",  
      "name": "Depression / Major Depressive Disorder",  
      "url": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/",  
      "description": "Depression is a mood disorder characterized by persistent feelings of sadness, loss of interest, fatigue, and cognitive impairments.",  
      "signOrSymptom": \[  
        { "@type": "MedicalSignOrSymptom", "name": "Persistent low mood" },  
        { "@type": "MedicalSignOrSymptom", "name": "Loss of interest or pleasure" },  
        { "@type": "MedicalSignOrSymptom", "name": "Fatigue or low energy" },  
        { "@type": "MedicalSignOrSymptom", "name": "Sleep disturbance" },  
        { "@type": "MedicalSignOrSymptom", "name": "Difficulty concentrating or making decisions" }  
      \],  
      "riskFactor": \[  
        { "@type": "MedicalRiskFactor", "name": "Family history of depression" },  
        { "@type": "MedicalRiskFactor", "name": "Chronic stress or trauma history" },  
        { "@type": "MedicalRiskFactor", "name": "Chronic medical conditions" }  
      \],  
      "typicalTest": \[  
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5 criteria)" },  
        { "@type": "MedicalTest", "name": "Depression rating scales (PHQ-9, HAM-D)" }  
      \],  
      "differentialDiagnosis": \[  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Anxiety disorders" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Chronic medical conditions" } }  
      \],  
      "possibleTreatment": \[  
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },  
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },  
        { "@type": "MedicalTherapy", "name": "Mindfulness-Based Cognitive Therapy (MBCT)" },  
        {  
          "@type": "MedicalTherapy",  
          "name": "Medication management",  
          "drug": { "@type": "Drug", "name": "Antidepressants (SSRIs, SNRIs, etc.)" }  
        }  
      \],  
      "primaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Early psychotherapy for at-risk individuals" },  
        { "@type": "MedicalTherapy", "name": "Lifestyle adjustments (exercise, sleep hygiene, social support)" }  
      \],  
      "secondaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Relapse prevention planning" },  
        { "@type": "MedicalTherapy", "name": "Ongoing psychotherapy follow-up" }  
      \],  
      "epidemiology": "Common in adults, with higher prevalence in females; onset often in late adolescence to early adulthood."  
    },  
    {  
      "@type": "Offer",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#offer",  
      "url": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/",  
      "availability": "https://schema.org/InStock",  
      "itemOffered": {  
        "@type": "Service",  
        "serviceType": "Depression treatment",  
        "provider": {  
          "@id": "https://missionconnectionhealthcare.com/\#org"  
        }  
      }  
    },  
    {  
      "@type": "FAQPage",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#faq",  
      "mainEntity": \[  
        {  
          "@type": "Question",  
          "name": "What is depression?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Depression is a mood disorder characterized by persistent sadness, loss of interest, and a range of physical and cognitive symptoms."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "How is depression treated?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Treatment may include psychotherapy such as CBT, DBT, or MBCT, along with medication management using antidepressants."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Who is at risk for depression?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Individuals with a family history of depression, chronic stress, trauma history, or certain medical conditions are at higher risk."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Can depression be prevented?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Early therapy for at-risk individuals and lifestyle interventions, such as regular exercise, adequate sleep, and strong social support, can help prevent depression."  
          }  
        }  
      \]  
    }  
  \]  
}  
\</script\>  
MEDICAL CONDITION EXAMPLE SCHEMA TYPE 3 for DEPRESSION: with multiple locations [https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/](https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/)   
![][image3]  
\<script type="application/ld+json"\>  
{  
  "@context": "https://schema.org",  
  "@graph": \[  
    {  
      "@type": "MedicalOrganization",  
      "@id": "https://missionconnectionhealthcare.com/\#org",  
      "name": "Mission Connection Healthcare",  
      "url": "https://missionconnectionhealthcare.com/",  
      "logo": "https://missionconnectionhealthcare.com/wp-content/uploads/2024/03/logo.svg",  
      "description": "Mission Connection Healthcare offers specialized mental health treatment services, including support for depression.",  
      "contactPoint": {  
        "@type": "ContactPoint",  
        "telephone": "+1-555-555-5555",  
  
        "areaServed": "US",  
        "availableLanguage": "English"  
      },  
      "department": \[  
        {  
          "@type": "MedicalClinic",  
          "name": "Mission Connection",  
          "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "2900 S. Quincy St \#810",  
            "addressLocality": "Arlington",  
            "addressRegion": "VA",  
            "postalCode": "22206",  
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
            "addressCountry": "US"  
          }  
        },  
        {  
          "@type": "MedicalClinic",  
          "name": "Mission Connection",  
          "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "11000 NE 33rd Pl \#340",  
            "addressLocality": "Bellevue",  
            "addressRegion": "WA",  
            "postalCode": "98004",  
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
            "addressCountry": "US"  
          }  
        }  
      \]  
    },  
    {  
      "@type": "MedicalCondition",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#condition",  
      "name": "Depression / Major Depressive Disorder",  
      "url": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/",  
      "description": "Depression is a mood disorder characterized by persistent feelings of sadness, loss of interest, fatigue, and cognitive impairments.",  
      "signOrSymptom": \[  
        { "@type": "MedicalSignOrSymptom", "name": "Persistent low mood" },  
        { "@type": "MedicalSignOrSymptom", "name": "Loss of interest or pleasure" },  
        { "@type": "MedicalSignOrSymptom", "name": "Fatigue or low energy" },  
        { "@type": "MedicalSignOrSymptom", "name": "Sleep disturbance" },  
        { "@type": "MedicalSignOrSymptom", "name": "Difficulty concentrating or making decisions" }  
      \],  
      "riskFactor": \[  
        { "@type": "MedicalRiskFactor", "name": "Family history of depression" },  
        { "@type": "MedicalRiskFactor", "name": "Chronic stress or trauma history" },  
        { "@type": "MedicalRiskFactor", "name": "Chronic medical conditions" }  
      \],  
      "typicalTest": \[  
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5 criteria)" },  
        { "@type": "MedicalTest", "name": "Depression rating scales (PHQ-9, HAM-D)" }  
      \],  
      "differentialDiagnosis": \[  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Anxiety disorders" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Chronic medical conditions" } }  
      \],  
      "possibleTreatment": \[  
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },  
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },  
        { "@type": "MedicalTherapy", "name": "Mindfulness-Based Cognitive Therapy (MBCT)" },  
        {  
          "@type": "MedicalTherapy",  
          "name": "Medication management",  
          "drug": { "@type": "Drug", "name": "Antidepressants (SSRIs, SNRIs, etc.)" }  
        }  
      \],  
      "primaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Early psychotherapy for at-risk individuals" },  
        { "@type": "MedicalTherapy", "name": "Lifestyle adjustments (exercise, sleep hygiene, social support)" }  
      \],  
      "secondaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Relapse prevention planning" },  
        { "@type": "MedicalTherapy", "name": "Ongoing psychotherapy follow-up" }  
      \],  
      "epidemiology": "Common in adults, with higher prevalence in females; onset often in late adolescence to early adulthood."  
    },  
    {  
      "@type": "Offer",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#offer",  
      "url": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/",  
      "availability": "https://schema.org/InStock",  
      "itemOffered": {  
        "@type": "Service",  
        "serviceType": "Depression treatment",  
        "provider": {  
          "@id": "https://missionconnectionhealthcare.com/\#org"  
        }  
      }  
    },  
    {  
      "@type": "FAQPage",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/depression/treatment-approaches/\#faq",  
      "mainEntity": \[  
        {  
          "@type": "Question",  
          "name": "What is depression?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Depression is a mood disorder characterized by persistent sadness, loss of interest, and a range of physical and cognitive symptoms."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "How is depression treated?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Treatment may include psychotherapy such as CBT, DBT, or MBCT, along with medication management using antidepressants."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Who is at risk for depression?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Individuals with a family history of depression, chronic stress, trauma history, or certain medical conditions are at higher risk."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Can depression be prevented?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "Early therapy for at-risk individuals and lifestyle interventions, such as regular exercise, adequate sleep, and strong social support, can help prevent depression."  
          }  
        }  
      \]  
    }  
  \]  
}  
\</script\>

MEDICAL CONDITION SCHEMA EXAMPLE: [https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/](https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/) (without multiple locations added)![][image4]  
\<script type="application/ld+json"\>  
{  
  "@context": "https://schema.org",  
  "@graph": \[  
    {  
      "@type": "MedicalOrganization",  
      "@id": "https://missionconnectionhealthcare.com/\#org",  
      "name": "Mission Connection Healthcare",  
      "url": "https://missionconnectionhealthcare.com/",  
      "logo": "https://missionconnectionhealthcare.com/wp-content/uploads/2024/03/logo.svg",  
      "description": "Mission Connection Healthcare offers specialized mental health treatment services, including support for anger issues and Intermittent Explosive Disorder (IED).",  
      "contactPoint": {  
        "@type": "ContactPoint",  
        "telephone": "+1-555-555-5555",  
  
        "areaServed": "US",  
        "availableLanguage": "English"  
      }  
    },  
    {  
      "@type": "MedicalCondition",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/\#condition",  
      "name": "Intermittent Explosive Disorder (IED)",  
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",  
      "description": "A behavioral disorder marked by recurrent, impulsive aggressive outbursts that are disproportionate to stressors.",  
      "signOrSymptom": \[  
        { "@type": "MedicalSignOrSymptom", "name": "Recurrent aggressive outbursts" },  
        { "@type": "MedicalSignOrSymptom", "name": "Irritability" },  
        { "@type": "MedicalSignOrSymptom", "name": "Impulsivity" }  
      \],  
      "riskFactor": \[  
        { "@type": "MedicalRiskFactor", "name": "History of trauma" },  
        { "@type": "MedicalRiskFactor", "name": "Early life adversity" },  
        { "@type": "MedicalRiskFactor", "name": "Substance misuse" }  
      \],  
      "typicalTest": \[  
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5-TR criteria)" },  
        { "@type": "MedicalTest", "name": "Psychological evaluation / standardized anger assessments" }  
      \],  
      "differentialDiagnosis": \[  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Borderline personality disorder" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Antisocial personality disorder" } }  
      \],  
      "possibleTreatment": \[  
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },  
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },  
        {  
          "@type": "MedicalTherapy",  
          "name": "Medication management",  
          "drug": { "@type": "Drug", "name": "Selective serotonin reuptake inhibitors (SSRIs)" }  
        }  
      \],  
      "primaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Anger management education" },  
        { "@type": "MedicalTherapy", "name": "Stress management and coping skills training" }  
      \],  
      "secondaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Relapse prevention plan and follow-up care" },  
        { "@type": "MedicalTherapy", "name": "Booster CBT/skills sessions" }  
      \],  
      "epidemiology": "Onset typically in adolescence; more common in males."  
    },  
    {  
      "@type": "Offer",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/\#offer",  
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",  
      "availability": "https://schema.org/InStock",  
      "itemOffered": {  
        "@type": "Service",  
        "serviceType": "Anger management & Intermittent Explosive Disorder treatment",  
        "provider": {  
          "@id": "https://missionconnectionhealthcare.com/\#org"  
        }  
      }  
    },  
    {  
      "@type": "FAQPage",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/\#faq",  
      "mainEntity": \[  
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
      \]  
    }  
  \]  
}  
\</script\>

MEDICAL CONDITION SCHEMA EXAMPLE: with multiple locations added   
[https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/](https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/) ![][image5]  
\<script type="application/ld+json"\>  
{  
  "@context": "https://schema.org",  
  "@graph": \[  
    {  
      "@type": "MedicalOrganization",  
      "@id": "https://missionconnectionhealthcare.com/\#org",  
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
      "department": \[  
        {  
          "@type": "MedicalClinic",  
          "name": "Mission Connection",  
          "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "2900 S. Quincy St \#810",  
            "addressLocality": "Arlington",  
            "addressRegion": "VA",  
            "postalCode": "22206",  
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
            "addressCountry": "US"  
          }  
        },  
        {  
          "@type": "MedicalClinic",  
          "name": "Mission Connection",  
          "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "11000 NE 33rd Pl \#340",  
            "addressLocality": "Bellevue",  
            "addressRegion": "WA",  
            "postalCode": "98004",  
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
            "addressCountry": "US"  
          }  
        }  
      \]  
    },  
    {  
      "@type": "MedicalCondition",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/\#condition",  
      "name": "Intermittent Explosive Disorder (IED)",  
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",  
      "description": "A behavioral disorder marked by recurrent, impulsive aggressive outbursts that are disproportionate to stressors.",  
      "signOrSymptom": \[  
        { "@type": "MedicalSignOrSymptom", "name": "Recurrent aggressive outbursts" },  
        { "@type": "MedicalSignOrSymptom", "name": "Irritability" },  
        { "@type": "MedicalSignOrSymptom", "name": "Impulsivity" }  
      \],  
      "riskFactor": \[  
        { "@type": "MedicalRiskFactor", "name": "History of trauma" },  
        { "@type": "MedicalRiskFactor", "name": "Early life adversity" },  
        { "@type": "MedicalRiskFactor", "name": "Substance misuse" }  
      \],  
      "typicalTest": \[  
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5-TR criteria)" },  
        { "@type": "MedicalTest", "name": "Psychological evaluation / standardized anger assessments" }  
      \],  
      "differentialDiagnosis": \[  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Borderline personality disorder" } },  
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Antisocial personality disorder" } }  
      \],  
      "possibleTreatment": \[  
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },  
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },  
        {  
          "@type": "MedicalTherapy",  
          "name": "Medication management",  
          "drug": { "@type": "Drug", "name": "Selective serotonin reuptake inhibitors (SSRIs)" }  
        }  
      \],  
      "primaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Anger management education" },  
        { "@type": "MedicalTherapy", "name": "Stress management and coping skills training" }  
      \],  
      "secondaryPrevention": \[  
        { "@type": "MedicalTherapy", "name": "Relapse prevention plan and follow-up care" },  
        { "@type": "MedicalTherapy", "name": "Booster CBT/skills sessions" }  
      \],  
      "epidemiology": "Onset typically in adolescence; more common in males."  
    },  
    {  
      "@type": "Offer",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/\#offer",  
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",  
      "availability": "https://schema.org/InStock",  
      "itemOffered": {  
        "@type": "Service",  
        "serviceType": "Anger management & Intermittent Explosive Disorder treatment",  
        "provider": {  
          "@id": "https://missionconnectionhealthcare.com/\#org"  
        }  
      }  
    },  
    {  
      "@type": "FAQPage",  
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/\#faq",  
      "mainEntity": \[  
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
      \]  
    }  
  \]  
}  
\</script\>

MEDICAL THERAPY SCHEMA EXAMPLE: [https://missionconnectionhealthcare.com/our-approach/emdr/](https://missionconnectionhealthcare.com/our-approach/emdr/)   
[![][image6]](https://missionconnectionhealthcare.com/our-approach/emdr/)  
\<script type="application/ld+json"\>  
{  
  "@context": "https://schema.org",  
  "@graph": \[  
    {  
      "@type": "MedicalOrganization",  
      "@id": "https://missionconnectionhealthcare.com/\#org",  
      "name": "Mission Connection Healthcare",  
      "url": "https://missionconnectionhealthcare.com/",  
      "logo": "https://missionconnectionhealthcare.com/wp-content/uploads/2024/03/logo.svg",  
      "description": "Mission Connection Healthcare offers specialized mental health treatments, including trauma-focused therapies such as EMDR.",  
      "contactPoint": {  
        "@type": "ContactPoint",  
        "telephone": "+1-555-555-5555",  
  
        "areaServed": "US",  
        "availableLanguage": "English"  
      },  
      "department": \[  
        {  
          "@type": "MedicalClinic",  
          "name": "Mission Connection",  
          "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "2900 S. Quincy St \#810",  
            "addressLocality": "Arlington",  
            "addressRegion": "VA",  
            "postalCode": "22206",  
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
            "addressCountry": "US"  
          }  
        },  
        {  
          "@type": "MedicalClinic",  
          "name": "Mission Connection",  
          "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "11000 NE 33rd Pl \#340",  
            "addressLocality": "Bellevue",  
            "addressRegion": "WA",  
            "postalCode": "98004",  
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
            "addressCountry": "US"  
          }  
        }  
      \]  
    },  
    {  
      "@type": "MedicalTherapy",  
      "@id": "https://missionconnectionhealthcare.com/our-approach/emdr/\#therapy",  
      "name": "Eye Movement Desensitization and Reprocessing (EMDR) Therapy",  
      "alternateName": "EMDR",  
      "additionalType": "https://schema.org/PsychologicalTreatment",  
      "url": "https://missionconnectionhealthcare.com/our-approach/emdr/",  
      "description": "EMDR is a structured therapy designed to alleviate distress associated with traumatic memories, helping clients process and integrate traumatic experiences. EMDR is recognized as effective for PTSD and trauma-related disorders.",  
      "howPerformed": "Therapist-guided sessions using bilateral stimulation techniques, such as eye movements or taps, to facilitate memory processing.",  
      "adverseOutcome": "May not be suitable for individuals with severe dissociative disorders."  
    },  
    {  
      "@type": "Service",  
      "@id": "https://missionconnectionhealthcare.com/our-approach/emdr/\#service",  
      "name": "EMDR Therapy Service",  
      "url": "https://missionconnectionhealthcare.com/our-approach/emdr/",  
      "provider": {  
        "@id": "https://missionconnectionhealthcare.com/\#org"  
      },  
      "audience": {  
        "@type": "Audience",  
        "audienceType": "Adults and adolescents experiencing trauma or PTSD"  
      }  
    },  
    {  
      "@type": "FAQPage",  
      "@id": "https://missionconnectionhealthcare.com/our-approach/emdr/\#faq",  
      "mainEntity": \[  
        {  
          "@type": "Question",  
          "name": "What is EMDR therapy?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "EMDR therapy is a structured psychotherapy that helps clients process traumatic memories and reduce associated emotional distress."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "How does EMDR work?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "EMDR uses bilateral stimulation, such as guided eye movements or taps, to help the brain process and integrate traumatic memories."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "Who can benefit from EMDR?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "EMDR is effective for adults and adolescents experiencing trauma, PTSD, or distressing memories."  
          }  
        },  
        {  
          "@type": "Question",  
          "name": "How many sessions are typically needed?",  
          "acceptedAnswer": {  
            "@type": "Answer",  
            "text": "The number of EMDR sessions varies depending on the severity and nature of the trauma, typically ranging from 6 to 12 sessions."  
          }  
        }  
      \]  
    }  
  \]  
}  
\</script\>

[image1]: <data:image/png;base64,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96OampqwCQAAioz+SLaL4m8GVVXVLsxdzx/rCk0KJzqYdiQ3ex91FApyuq7QZk1DtH8PfvFFWP2DpyQDXEdiN1DorwaZNgMHAAAAUMr84AKcf12dade7AQAAAGSBH1yAE3rIou5+1TV3mpoFAAAAyBI/yAAHAAAAkGUIcAAAAAAZgwAHAAAAkCH0xQR6QgcBDgAAACAj1NbWRg0NDdGFCxcIcAAAAABZoLq62n1lGgEOAAAAIANo9k3PtNVXwOkhyAQ4AAAAgBJG175VVFRE9fX1UUtLSxzeCHAAAAAAJYjCm74CVF/xqWV/9o0ABwAAAFBi6I7TPXv2RJWVle7mhXD2zQU4Tc3p/KrSXUd/MS8AAAAAJFH+kspjymWaeaurq3PZzL9xIRHgdGeDOpaXl0e7d++OysrKEBEREbGTVP5SDlMeUy7TQ3t100K+8OYCnD0c7tChQ+48q1ZERERExM5R+Us5THlMuUynTMNr3kK7qIOm7Zqbm13aQ0RERMTOVTlMeay94BYHOL+glRARERGxcw0DWnsmAhwiIiIilr4EOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSMSYBDREREzJgEOERERMSM+f/a/AwknYsQFgAAAABJRU5ErkJggg==>

[image2]: <data:image/png;base64,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>

[image3]: <data:image/png;base64,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>

[image4]: <data:image/png;base64,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>

[image5]: <data:image/png;base64,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>

[image6]: <data:image/png;base64,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>