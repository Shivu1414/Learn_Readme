# Project: Wix| Auction

## Version 1.0.0

## Description
This application will help the admin to organize an online auction of their products. The store customers will make their bid and the admin can see the bids that customer made on his product.


## Feature
1. Admin is able to sync store products.
2. Admin can create Auctions by using Create Auction button on the Manage Auction Page.
3. If admin select multiple product, multiple auction are creted.

## Important Points
1. Application does not provide functionality to sync the digital product from the store.
2. Webhooks are implemented to create a new product and update the existing product.

## Terminology(Developer's Points)
1. Base Price: The amount from where the Auction will be started.
2. Reserve Price: Lowest amount at which seller is willing to sell an item.
3. Status:

    R => RUNNING 

    A => ACTIVE 

    D => STOPPED

4. Auctions Status

    autostart->1 and TIME >=from_time ->>running

    autostart->1 and TIME < from_time ->>ACTIVE

    autostart->0 and TIME >= from_time ->>ACTIVE

    autostart->1 and TIME < from_time ->>ACTIVE

    autostart->1 and TIME > to_time ->> Stopped

    stop manually ->>STOPPED

    TIME > to_time ->>STOPPED


## DFD of the application
https://imgur.com/a/eGxYPBC