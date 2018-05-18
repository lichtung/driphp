# MySQL

## 备份和恢复类型

### 物理备份与逻辑备份 （Physical (Raw) Versus Logical Backups）
Physical backups consist of raw copies of the directories and files that store database contents. 
This type of backup is suitable for large, important databases that need to be recovered quickly when problems occur.
物理备份指的是拷贝数据库目录和文件，它适合体量大、数据重要的数据库快速恢复的场景。

Logical backups save information represented as logical database structure (CREATE DATABASE, CREATE TABLE statements) 
and content (INSERT statements or delimited-text files). 
This type of backup is suitable for smaller amounts of data where you might edit the data values or table structure, 
or recreate the data on a different machine architecture.
逻辑备份数据通过保存数据库的结构创建语句和插入语句实现。它适合数据量小的数据库或者在计算机架构下复制数据。

### 在线备份和离线备份／热备份和冷备份 （Online Versus Offline Backups）
Online backups take place while the MySQL server is running so that the database information can be obtained from the server. 
Offline backups take place while the server is stopped.
This distinction can also be described as “hot” versus “cold” backups; a “warm” backup is one where the server remains running 
but locked against modifying data while you access database files externally.
在线备份是在MySQL服务器运行期间进行备份，而离线备份则发生在MySQL停止运行期间。它们的区别可以被描述成**冷备份**和**热备份**，
另外还有**温备份**，即服务器仍然在运行，但是对数据的修改的操作会被锁定。

冷备份的特点：
- The backup is less intrusive to other clients, which can connect to the MySQL server during the backup and may be able 
to access data depending on what operations they need to perform.
这种备份可以不影响客户端的连接，客户端对数据的任意访问
- Care must be taken to impose appropriate locking so that data modifications do not take place that would compromise backup 
integrity. The MySQL Enterprise Backup product does such locking automatically.
必须注意的是，备份期间需要施加适当的锁以防数据修改导致数据的完整性受影响。MySQL企业版备份不会自动锁定。

### 本地备份和远程备份 （Local Versus Remote Backups）
A local backup is performed on the same host where the MySQL server runs, whereas a remote backup is done from a different host. 
For some types of backups, the backup can be initiated from a remote host even if the output is written locally on the server. host.

### 快照备份 （Snapshot Backups）
Some file system implementations enable “snapshots” to be taken. These provide logical copies of the file system at a given point in time, 
without requiring a physical copy of the entire file system. (For example, the implementation may use copy-on-write techniques so that 
only parts of the file system modified after the snapshot time need be copied.) MySQL itself does not provide the capability for 
taking file system snapshots. It is available through third-party solutions such as Veritas, LVM, or ZFS.
一些文件系统在实现上支持**快照**。MySQL本身不提供快照技术，

### 全量备份和增量备份 （Full Versus Incremental Backups）
A full backup includes all data managed by a MySQL server at a given point in time（在适当时间）. An incremental backup consists of the 
changes made to the data during a given time span (from one point in time to another). MySQL has different ways to perform 
full backups, such as those described earlier in this section. Incremental backups are made possible by enabling the server's 
binary log, which the server uses to record data changes.
全量备份指的是在给定的时间点备份所有MySQL管理的数据。
增量备份包含在指定的时间区间内对数据的所有修改。
MySQL有很多进行全量备份的方法，增量备份是通过激活服务器的bin-log（服务器用来记录数据变化）来实现的


### 全量恢复和增量恢复 （Full Versus Point-in-Time (Incremental) Recovery）
A full recovery restores all data from a full backup. This restores the server instance to the state that it had when the backup
was made. If that state is not sufficiently current, a full recovery can be followed by recovery of incremental backups made since
the full backup, to bring the server to a more up-to-date state.

Incremental recovery is recovery of changes made during a given time span. This is also called point-in-time recovery because it 
makes a server's state current up to a given time. Point-in-time recovery is based on the binary log and typically follows a full
recovery from the backup files that restores the server to its state when the backup was made. Then the data changes written in 
the binary log files are applied as incremental recovery to redo data modifications and bring the server up to the desired point 
in time.

## 备份方法
### 热备份
MySQL企业版提供了备份工具
### mysqldump

mysqldump输出dump文件，dump的作用如下：
- 数据恢复
- 设置从服务器数据
- 数据测试

mysqldump提供了两种输出：
- 不带--tab
Without --tab, mysqldump writes SQL statements to the standard output. This output consists of CREATE statements to create dumped objects (databases, tables, stored routines, and so forth), and INSERT statements to load data into tables. The output can be saved in a file and reloaded later using mysql to recreate the dumped objects. Options are available to modify the format of the SQL statements, and to control which objects are dumped.

- 带--tab
With --tab, mysqldump produces two output files for each dumped table. The server writes one file as tab-delimited text, one line per table row. This file is named tbl_name.txt in the output directory. The server also sends a CREATE TABLE statement for the table to mysqldump, which writes it as a file named tbl_name.sql in the output directory.
#### 输出sql格式
mysqldump默认将输出打印到终端上，如果要输出到文件中，可以使用下面的命令：
```bash
mysqldump [arguments] > file_name
```
可以选择数据库
```bash
# 输出全部数据库
mysqldump --all-databases > dump.sql
# 输出指定的数据库
mysqldump --databases db1 db2 db3 > dump.sql

```
练习：
```bash
# 省略--databases的情况下，输出的SQL中会少了CREATE DATABASE和USE语句
mysqldump --databases sharina -uroot -pasdqwe123_ZXC --flush-logs > a.sql
mysqldump             sharina -uroot -pasdqwe123_ZXC --flush-logs > b.sql
```

选项：
--add-drop-database     # Add DROP DATABASE statement before each CREATE DATABASE statement 
--add-drop-table        # Add DROP TABLE statement before each CREATE TABLE statement 
--add-drop-trigger      # Add DROP TRIGGER statement before each CREATE TRIGGER statement
...
详细参考[mysqldump](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html)



#### 执行SQL语句
在执行mysqldump时带上了--databases或者--all-databases的情况下，因为有了create database和use语句，所以不需要额外声明数据库
直接将sql语句输入到mysql客户端中
```bash
mysql < dump.sql
```
或者建立mysql连接以后，执行
```sql
source dump.sql
```
如果备份文件中没有create database和use语句,那么需要额外做如下的操作：
```bash
mysqladmin create [database_name]
mysql [database_name] < dump.sql
```
或者建立mysql连接以后，执行
```sql
CREATE DATABASE IF NOT EXISTS [database_name];
USE [database_name];
source dump.sql
```

练习：
```bash
mysql -uroot -pasdqwe123_ZXC < a.sql
# 或者
mysqladmin create sharina2 -uroot -pasdqwe123_ZXC
mysql sharina2 -uroot -pasdqwe123_ZXC < b.sql

```


https://dev.mysql.com/doc/refman/5.7/en/linux-installation-yum-repo.html


https://dev.mysql.com/downloads/repo/yum/
```bash
yum localinstall platform-and-version-specific-package-name.rpm
yum repolist enabled | grep "mysql.*-community.*"

```
### Binlog
每次重启mysql都会生成一个binlog文件，格式如"mysql-bin.XXXXXXXX"
### 创建增量备份 Making Incremental Backups by Enabling the Binary Log
