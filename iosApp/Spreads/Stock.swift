//
//  Stock.swift
//  Spreads
//
//  Created by Андрей Мостовой on 16.01.2018.
//  Copyright © 2018 Андрей Мостовой. All rights reserved.
//

import UIKit

class Stock {

    let name: String
    let buyUSD: Float
    let buyEUR: Float
    let spreadEUR: Int
    let spreadUSD: Int
    let percentEUR: Int
    let percentUSD: Int

    init?(name: String, buyEUR: Float, spreadEUR: Int, percentEUR: Int, buyUSD: Float, spreadUSD: Int, percentUSD: Int) {
        self.name = name
        self.buyEUR = buyEUR
        self.spreadEUR = spreadEUR
        self.percentEUR = percentEUR
        self.buyUSD = buyUSD
        self.spreadUSD = spreadUSD
        self.percentUSD = percentUSD
    }
}
