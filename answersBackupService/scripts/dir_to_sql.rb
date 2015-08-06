#!/usr/bin/env ruby
# encoding: UTF-8

require 'date'
require 'oj'
require 'multi_json'
require 'file/tail'
require 'set'

class TFile < File
	include File::Tail
end

class ProgressLogger

	def initialize
		@counters = Hash.new
		@bumps = 0
		@bumps_limit = 100
		@total_bumps = 0
		@total_secs = 0
		@ts = Time.now
	end

	def bump(sym, count = 1)
		@counters[sym] = count + (@counters[sym] || 0)
		@bumps += count
		maybe_log if @bumps > @bumps_limit
	end

	def maybe_log
		now = Time.now
		if now - @ts > 1
			@ts = now
			@total_secs += 1
			@total_bumps += @bumps
			@bumps_limit = @total_bumps / @total_secs
			@bumps = 0
			log
		end
	end

	def log
		puts MultiJson.dump(@counters)
	end

end

##########
#
# DirReader reads answers from a directory and passes them to a writer.
#
class DirReader

	def initialize(writer, logger)
		@writer = writer
		@logger = logger
	end

	attr_reader :teams_read, :answers_read

	def run(path)
		Dir.glob("#{path}/*").each do |dir1|
			Dir.glob("#{dir1}/*").each do |dir2|
				Dir.glob("#{dir2}/*").each do |dir3|
					Dir.glob("#{dir3}/*").each do |dir4|
						Dir.glob("#{dir4}/*").each do |dir5|
							team_id = dir5.gsub(/[^0-9]+/, '').to_i
							resp = Hash.new
							sids = Set.new
							Dir.glob("#{dir5}/*").each do |fn|
								time = File.ctime(fn)
								if md = /[0-9]+$/.match(fn)
									question_id = md[0].to_i
									TFile.open(fn, 'r:ISO-8859-1') do |inf|
										inf.backward(1)
										last_line = inf.read
										unless last_line
											warn "no text in #{fn}"
											next
										end
										@logger.bump(:answers_read)
										line = last_line.encode('UTF-8')
										json = MultiJson.load(line)
										if answer = json['answer']
											sids.add(json['sid'])
											if team_id != json['team']
												puts "bad team_id in #{fn}"
												next
											end
											if question_id != json['question']
												puts "bad question_id in #{fn}"
												next
											end
											@writer.add_answer(team_id, question_id, answer, time)
										end
									end
								end
							end
							@logger.bump(:teams_read)
						end # dir5
					end # dir4
				end # dir3
			end # dir2
		end # dir1
	end

end

##########
#
# SqlWriter writes insert statements to a file by group of 1000 values.
#
class SqlWriter

	def initialize(out, logger)
		@out = out
		@logger = logger
		@rows = Array.new
	end
	
	attr_reader :rows_inserted

	def mysql_escape_string(str)
		str = str.gsub(/[\u0000\n\r\\'"\u001A]/) { |c| '\\'+c }
		"'#{str}'"
	end

	def output_insert
		values = @rows.map do |row|
			"(#{row.join(',')})"
		end
		@rows = Array.new
		@out.puts "INSERT INTO `team_question_sebc` (teamID, questionID, answer, date) VALUES #{values.join(',')};"
		@logger.bump(:rows_inserted, values.length)
	end

	def add_answer(team_id, question_id, answer, time)
		answer = mysql_escape_string(answer)
		time = mysql_escape_string(time.utc.strftime('%Y-%m-%d %H:%M:%S'))
		@rows.push([team_id, question_id.to_i, answer, time])
		output_insert if @rows.length >= 1000
	end

	def flush
		output_insert unless @rows.empty?
	end

end

#
# Driver
#

if ARGV.length < 2
	puts "arguments: <output-file> <input-dir> [<input-dir> ...]"
	exit 1
end

logger = ProgressLogger.new
out_fn = ARGV.shift
File.open(out_fn, 'w:UTF-8') do |out|
	writer = SqlWriter.new(out, logger)
	reader = DirReader.new(writer, logger)
	ARGV.each do |fn|
		reader.run(fn)
	end
	writer.flush
end
logger.log

