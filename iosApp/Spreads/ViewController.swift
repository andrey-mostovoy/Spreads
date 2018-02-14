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

    var RetreaveDataTimer: Timer!
    var UserRateEditTimer: Timer!

    var isUserChangeData = false

    var progressValue = 0.0
    var timeInterval = 5.0
    var progressDelay = 0.2
    var progressStep = 0.04

    override func viewDidLoad() {
        super.viewDidLoad()
        // Do any additional setup after loading the view, typically from a nib.

//        progressStep = 1.0 / (timeInterval / progressDelay)

        keyboardHandlers()

        self.TableView = (childViewControllers[0] as? StockTableViewController)
        self.isUserChangeData = false

        retreaveAndViewData()
        startTimer()
    }

    // all for make keyboard disapear
    func keyboardHandlers() {
        eurRate.delegate = self
        usdRate.delegate = self
        
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

    @IBAction func buttonTouchUpInside(_ sender: UIButton) {
        clearUserEditButton.isHidden = true
        isUserChangeData = false
        if (UserRateEditTimer != nil) {
            UserRateEditTimer.invalidate()
        }
        self.retreaveAndViewData()
        self.startTimer()
    }

    @objc func updateProgress() {
        if (!RetreaveDataTimer.isValid) {
            return
        }
        progressValue = progressValue + progressStep
        self.retrieveIntervalProgress.progress = Float(progressValue)
        if progressValue != 1.0 {
            self.perform(#selector(self.updateProgress), with: nil, afterDelay: progressDelay)
        }
    }
    
    func startTimer() {
        RetreaveDataTimer = Timer.scheduledTimer(timeInterval: 5, target: self, selector: #selector(self.update), userInfo: nil, repeats: true)

        startProgress()
    }

    func stopTimer() {
        RetreaveDataTimer.invalidate()
        stopProgress()
    }
    
    func startProgress() {
        progressValue = 0.0
        self.perform(#selector(self.updateProgress), with: nil, afterDelay: progressDelay)
    }

    func stopProgress() {
        NSObject.cancelPreviousPerformRequests(withTarget: self, selector: #selector(self.updateProgress), object: nil)
    }

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }

    @IBAction func rateEditingChanged(_ sender: UITextField) {
        self.isUserChangeData = true
        clearUserEditButton.isHidden = false

        stopTimer()

        if (UserRateEditTimer != nil) {
            UserRateEditTimer.invalidate()
        }

        if (eurRate.text?.suffix(1) != "." && usdRate.text?.suffix(1) != "." &&
            eurRate.text != "" && usdRate.text != ""
        ) {
            // for minimal request to server use 2 sec interval for start retrieving data
            UserRateEditTimer = Timer.scheduledTimer(withTimeInterval: 2.0, repeats: false, block: { (Timer) in
                self.retreaveAndViewData()
                self.startTimer()
            })
        }
    }

    @objc func update() {
        stopProgress()
        retreaveAndViewData()
    }

    @objc func updateWithUserData() {
        stopProgress()
        retreaveWithUserDataAndViewData()
    }

    func retreaveAndViewData() {
        if (self.isUserChangeData) {
            self.retreaveWithUserDataAndViewData()
        } else {
            Alamofire.request("http://165.227.185.180/run_api.php").responseJSON { (response) -> Void in
                if ((response.result.value) != nil) {
                    self.parseAndViewData(data: response.data)
                    self.startProgress()
                }
            }
        }
    }

    func retreaveWithUserDataAndViewData() {
        let eur = Float(eurRate.text!)
        let usd = Float(usdRate.text!)

        Alamofire.request(
            "http://165.227.185.180/run_api.php",
            method: .post,
            parameters: ["method": "manualCashRate", "cashRate": ["EUR": eur!, "USD": usd!]]
        ).responseJSON { (response) -> Void in
            if ((response.result.value) != nil) {
                self.parseAndViewData(data: response.data)
                self.startProgress()
            }
        }
    }

    private func parseAndViewData(data: Data?) {
        let parsedData = try! JSONDecoder().decode(StockExchangeData.self, from: data!)

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
}

