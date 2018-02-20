//
//  ViewController.swift
//  Spreads
//
//  Created by Андрей Мостовой on 15.01.2018.
//  Copyright © 2018 Андрей Мостовой. All rights reserved.
//

import UIKit
import Alamofire

class ViewController: UIViewController, UITextFieldDelegate {
    // data from server
    struct StockExchangeData: Codable {
        let stock: [String: StockStruct]
        let local: Float
        let cash: [String: Float]
        let timestamp: Int
    }
    struct StockStruct: Codable {
        let name: String
        let buy: [String: Float]
        let spread: [String: Int]
        let percent: [String: Int]
    }

    // MARK: Properties
    @IBOutlet weak var usdRate: UITextField!
    @IBOutlet weak var eurRate: UITextField!
    @IBOutlet weak var localPrice: UILabel!
    @IBOutlet weak var retrieveIntervalProgress: UIProgressView!
    @IBOutlet weak var clearUserEditButton: UIButton!

    var TableView: StockTableViewController?

    weak var RetreaveDataTimer: Timer!

    var isUserChangeData = false

    var progressValue = 0.0
    var timeInterval = 5.0
    var progressDelay = 0.2
    var progressStep = 0.04

    override func viewDidLoad() {
        super.viewDidLoad()
        // Do any additional setup after loading the view, typically from a nib.

//        progressStep = 1.0 / (timeInterval / progressDelay)

        self.keyboardHandlers()

        self.TableView = (childViewControllers[0] as? StockTableViewController)
        self.isUserChangeData = false

        self.retreaveAndViewData()
    }

    // all for make keyboard disapear
    func keyboardHandlers() {
        self.eurRate.delegate = self
        self.usdRate.delegate = self
        
        // for scroll and table views
        let tap = UITapGestureRecognizer(target: self.view, action: #selector(UIView.endEditing(_:)))
        tap.cancelsTouchesInView = false
        self.view.addGestureRecognizer(tap)
    }

    // make keyboard disapear on touch except scroll and table views
    override func touchesBegan(_ touches: Set<UITouch>, with event: UIEvent?) {
        self.view.endEditing(true)
    }

    // make keyboard disapear on return key
    func textFieldShouldReturn(_ textField: UITextField) -> Bool {
        textField.resignFirstResponder()
        
        return true
    }

    // edit text input event
    @IBAction func rateEditingChanged(_ sender: UITextField) {
        self.stopRetreaveTimer()

        self.isUserChangeData = true
        self.clearUserEditButton.isHidden = false

        if (self.eurRate.text?.suffix(1) == ",") {
            self.eurRate.text = self.eurRate.text?.replacingOccurrences(of: ",", with: ".")
        }
        if (self.usdRate.text?.suffix(1) == ",") {
            self.usdRate.text = self.usdRate.text?.replacingOccurrences(of: ",", with: ".")
        }

        if (self.eurRate.text?.suffix(1) != "." && self.usdRate.text?.suffix(1) != "." && self.eurRate.text != "" && self.usdRate.text != "") {

            self.retreaveAndViewData()
            self.startRetreaveTimer()
        }
    }

    // clear button pressed
    @IBAction func buttonTouchUpInside(_ sender: UIButton) {
        self.stopRetreaveTimer()

        self.clearUserEditButton.isHidden = true
        self.isUserChangeData = false

        self.retreaveAndViewData()
        self.startRetreaveTimer()
    }

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }

    // -------- retreave data timer
    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(animated)

        self.startRetreaveTimer()
    }
    
    override func viewDidDisappear(_ animated: Bool) {
        super.viewDidDisappear(animated)

        self.stopRetreaveTimer()
    }

    func startRetreaveTimer() {
        self.startProgress()
        self.RetreaveDataTimer = Timer.scheduledTimer(timeInterval: 5, target: self, selector: #selector(update(_:)), userInfo: nil, repeats: true)
    }

    func stopRetreaveTimer() {
        self.RetreaveDataTimer?.invalidate()
        self.stopProgress()
    }

    @objc func update(_ timer: Timer) {
        self.stopProgress()
        self.retreaveAndViewData()
        self.startProgress()
    }
    // ---------------------

    // -------- progress bar
    func startProgress() {
        self.progressValue = 0.0
        self.perform(#selector(self.updateProgress), with: nil, afterDelay: self.progressDelay)
    }

    func stopProgress() {
        NSObject.cancelPreviousPerformRequests(withTarget: self, selector: #selector(self.updateProgress), object: nil)
    }

    @objc func updateProgress() {
        if (self.RetreaveDataTimer == nil) {
            return
        }

        if (!self.RetreaveDataTimer.isValid) {
            return
        }
        self.progressValue = self.progressValue + self.progressStep
        self.retrieveIntervalProgress.progress = Float(self.progressValue)
        if (self.progressValue != 1.0) {
            self.perform(#selector(self.updateProgress), with: nil, afterDelay: self.progressDelay)
        }
    }
    // ---------------------

    // -------- retreave and show data
    func retreaveAndViewData() {
        if (self.isUserChangeData) {
            self.retreaveWithUserDataAndViewData()
        } else {
            DispatchQueue.global(qos: .userInitiated).async {
                Alamofire.request("http://165.227.185.180/run_api.php").responseJSON { (response) -> Void in
                    if ((response.result.value) != nil) {
                        self.parseAndViewData(data: response.data)
                    }
                }
            }
        }
    }

    func retreaveWithUserDataAndViewData() {
        let eur = Float(eurRate.text!)
        let usd = Float(usdRate.text!)

        DispatchQueue.global(qos: .userInitiated).async {
            Alamofire.request(
                "http://165.227.185.180/run_api.php",
                method: .post,
                parameters: ["method": "manualCashRate", "cashRate": ["EUR": eur, "USD": usd]]
                ).responseJSON { (response) -> Void in
                    if ((response.result.value) != nil) {
                        self.parseAndViewData(data: response.data)
                    }
            }
        }
    }
    
    private func parseAndViewData(data: Data?) {
        do {
            let parsedData = try JSONDecoder().decode(StockExchangeData.self, from: data!)
            
            DispatchQueue.main.async {
                self.localPrice.text = String(format: "%.5f", parsedData.local);
                if (!self.isUserChangeData) {
                    self.usdRate.text = String(format: "%.2f", parsedData.cash["USD"]!);
                    self.eurRate.text = String(format: "%.2f", parsedData.cash["EUR"]!);
                }
                
                self.TableView?.stocks = []
                
                for (_, Info) in parsedData.stock {
                    let StockObj = Stock(name: Info.name, buyEUR: Info.buy["EUR"]!, spreadEUR: Info.spread["EUR"]!, percentEUR: Info.percent["EUR"]!, buyUSD: Info.buy["USD"]!, spreadUSD: Info.spread["USD"]!, percentUSD: Info.percent["USD"]!)
                    self.TableView?.stocks.append(StockObj!)
                }
                self.TableView?.tableView.reloadData()
            }
        } catch {
            print("---- json parse error")
        }
    }
    // ---------------------
}
