---
title: Defining time series stats
sidebar_position: 3
---

Time series statistics are any statistic that varies over time. Using time series stats, you can track any numerical value that changes over time within a given time period. For example, this could be daily user signups, weekly sales, etc. Currently, this package allows viewing time series stats in the following modes:

- Hour
- Day
- Week 
- Month
- Year

So to define a time series stat, you have to provide the query for each of these modes, and a query to get the total for the full date range.
Luckily, this package makes the process easy for you by providing a set of abstract Stat Repository classes that you can extend to define your stat.

# Aggregate Stats



# Count Stats



