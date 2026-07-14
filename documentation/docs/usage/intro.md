---
sidebar_position: 1
title: Introduction
---

# Introduction

## Lexicon

Let's define a few terms first:

* **Instance**: a running eLabFTW service, for example: https://eln.example.org
* **Team**: the main unit for a group of users
* **Sysadmin**: technical role: a user with Sysadmin rights can modify the Instance configuration and create Teams, it is generally the same person that installed the Instance and manages the server.  See the [Sysadmin guide](/docs/usage/sysadmin-guide).
* **Admin**: a user with Admin rights for a given team has access to the Admin Panel and can manage settings related to their Team. A given user can be Admin in Team A and User in Team B. See the [Admin guide](/docs/usage/admin-guide). Some Admins can also have the right to affect users from other teams.
* **User**: a user with an account on the Instance, belonging to at least one Team. See the [User guide](/docs/category/user-guide).

We could also mention the role of **Instance Coordinator**, someone identified by the Users as the person to go to for all things eLab in the institution. It might be Research Data Managers, or a designated Researcher or Engineer that is very familiar with eLabFTW. This person could have access to managing the relationship between users and teams. They could animate the internal chat room related to eLabFTW usage. See the [Instance Coordinator documentation page](/docs/usage/coordinator-guide).

## General principles

One eLabFTW instance can host several Teams.

Every User needs to belong to at least one Team. Every team must have at least one Admin user.

~~~html

      ┌────────────────────────┐
      │                        │
      │    eLabFTW Instance    │
      │                        │
      └────┬───────────┬─────┬─┘       ┌──────────┐
           │           │     │         │  Team C  │
           │           │     │         ├──────────┤
           │           │     └──────►  │ Admin 1  │
           │           │               │ User 1   │
           ▼           ▼               │ User 2   │
    ┌──────────┐    ┌──────────┐       │ User 3   │
    │  Team A  │    │  Team B  │       │ User 4   │
    ├──────────┤    ├──────────┤       └──────────┘
    │ Admin 1  │    │ Admin 1  │
    │ User 1   │    │ Admin 2  │
    │ User 2   │    │ Admin 3  │
    │ User 3   │    │ User 1   │
    └──────────┘    │ User 2   │
                    │ User 3   │
                    └──────────┘
~~~

## Teams

A Team generally correspond to a real life research group or service. It is not advisable to use Teams as Projects as this doesn't scale well (and Teamgroups can be used instead for that purpose). A User can belong to several teams if needed, they will then need to select a team upon login.

Every Team has one or several Admin, who can change many settings affecting users in the team, such as the default experimental template, categories for database items (Items Types), experiments Status, Tags, etc...

Teams are created by the Sysadmin from the Sysconfig page ([see documentation](/docs/usage/sysadmin-guide#configure-teams-optional)).

## Experiments and Resources

There are two main types of records that exist: **Experiments** and **Resources**. They are listed in their corresponding page, accessible from the main top menu.

**Experiments** are the main aspect of the ELN: it is where users log their results.

**Resources** correspond to a repository of *things* present in the lab, generally physical things, such as plasmids, equipment, antibodies, chemical products, etc... They have different properties than experiments, although both objects are similar in many aspects. A Resource can be made bookable (so they can be booked in the Scheduler) or procurable (so we can order more of it).

By default, Experiments and Resources are restricted to a team. But users can choose to extend this to all registered users or even anonymous users if enabled by the Sysadmin. An extensive fine-grained permission system is available for both types of records and their corresponding templates to have complete control on who get to read/write what.

## User accounts

There are several ways to manage users, they can be created by a Sysadmin, an Admin, during authentication with centralized directory, or users can create their account directly.

Users can directly create their account on the register page (`/register.php`), accessible from the login page. They will need to select a team from the list.

By default, newly created accounts are disabled. The admin of the team needs to validate them by going into the admin panel and activate new users.

It is also possible to provision users or use an external authentication service such as an Identity Provider (IdP/SAML2) or LDAP directory.
