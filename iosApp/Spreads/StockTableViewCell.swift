//
//  StockTableViewCell.swift
//  Spreads
//
//  Created by Андрей Мостовой on 16.01.2018.
//  Copyright © 2018 Андрей Мостовой. All rights reserved.
//

import UIKit

class StockTableViewCell: UITableViewCell {

    // MARK: Properties

    @IBOutlet weak var name: UILabel!
    @IBOutlet weak var spreadEUR: UILabel!
    @IBOutlet weak var spreadUSD: UILabel!
    @IBOutlet weak var buyEUR: UILabel!
    @IBOutlet weak var buyUSD: UILabel!
    @IBOutlet weak var percentEUR: UILabel!
    @IBOutlet weak var percentUSD: UILabel!
    
    override func awakeFromNib() {
        super.awakeFromNib()
        // Initialization code
    }

    override func setSelected(_ selected: Bool, animated: Bool) {
        super.setSelected(selected, animated: animated)

        // Configure the view for the selected state
    }

}
