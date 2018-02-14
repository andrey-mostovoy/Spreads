//
//  StockTableViewController.swift
//  Spreads
//
//  Created by Андрей Мостовой on 16.01.2018.
//  Copyright © 2018 Андрей Мостовой. All rights reserved.
//

import UIKit

class StockTableViewController: UITableViewController {
    var stocks = [Stock]()

    override func viewDidLoad() {
        super.viewDidLoad()

        // Uncomment the following line to preserve selection between presentations
        // self.clearsSelectionOnViewWillAppear = false

        // Uncomment the following line to display an Edit button in the navigation bar for this view controller.
        // self.navigationItem.rightBarButtonItem = self.editButtonItem
    }

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }

    // MARK: - Table view data source

    override func numberOfSections(in tableView: UITableView) -> Int {
        // #warning Incomplete implementation, return the number of sections
        return 1
    }

    override func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        // #warning Incomplete implementation, return the number of rows
        return stocks.count
    }

    override func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        // Table view cells are reused and should be dequeued using a cell identifier.
        let cellIdentifier = "StocksInfo"
        guard let cell = tableView.dequeueReusableCell(withIdentifier: cellIdentifier, for: indexPath) as? StockTableViewCell
            else {
                fatalError("The dequeued cell is not an instance of StockTableViewCell.")
        }

        // Configure the cell...

        // Fetches the appropriate stock for the data source layout.
        let stock = stocks[indexPath.row]

        cell.name.text = stock.name
        cell.buyEUR.text = String(format: "%.5f", stock.buyEUR)
        cell.spreadEUR.text = String(stock.spreadEUR)
        cell.percentEUR.text = "(" + String(stock.percentEUR) + "%)"
        cell.buyUSD.text = String(format: "%.5f", stock.buyUSD)
        cell.spreadUSD.text = String(stock.spreadUSD)
        cell.percentUSD.text = "(" + String(stock.percentUSD) + "%)"

        if stock.name.lowercased() == "binance" {
            cell.buyEUR.isHidden = true
            cell.spreadEUR.isHidden = true
            cell.percentEUR.isHidden = true
        } else {
            cell.buyEUR.isHidden = false
            cell.spreadEUR.isHidden = false
            cell.percentEUR.isHidden = false
        }

        return cell
    }

    /*
    // Override to support conditional editing of the table view.
    override func tableView(_ tableView: UITableView, canEditRowAt indexPath: IndexPath) -> Bool {
        // Return false if you do not want the specified item to be editable.
        return true
    }
    */

    /*
    // Override to support editing the table view.
    override func tableView(_ tableView: UITableView, commit editingStyle: UITableViewCellEditingStyle, forRowAt indexPath: IndexPath) {
        if editingStyle == .delete {
            // Delete the row from the data source
            tableView.deleteRows(at: [indexPath], with: .fade)
        } else if editingStyle == .insert {
            // Create a new instance of the appropriate class, insert it into the array, and add a new row to the table view
        }    
    }
    */

    /*
    // Override to support rearranging the table view.
    override func tableView(_ tableView: UITableView, moveRowAt fromIndexPath: IndexPath, to: IndexPath) {

    }
    */

    /*
    // Override to support conditional rearranging of the table view.
    override func tableView(_ tableView: UITableView, canMoveRowAt indexPath: IndexPath) -> Bool {
        // Return false if you do not want the item to be re-orderable.
        return true
    }
    */

    /*
    // MARK: - Navigation

    // In a storyboard-based application, you will often want to do a little preparation before navigation
    override func prepare(for segue: UIStoryboardSegue, sender: Any?) {
        // Get the new view controller using segue.destinationViewController.
        // Pass the selected object to the new view controller.
    }
    */

}
